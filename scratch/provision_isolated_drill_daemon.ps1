# scratch/provision_isolated_drill_daemon.ps1
# ==============================================================================
# TopUpers Gate 2B -- Isolated MariaDB 10.4.32 Restore-Drill Daemon Provisioner
# ==============================================================================
# NON-EXECUTED IMPLEMENTATION / SPECIFICATION-ALIGNED SCRIPT
# Status: STRICT HOLD. Gate 2B migration and live database modification are prohibited.
#
# PURPOSE:
# Provisions a temporary, strictly isolated MariaDB 10.4.32 daemon on dedicated port 3308
# with an ephemeral data directory located outside the web root (C:\xampp\htdocs\) and
# completely decoupled from the production MariaDB service and data directory (C:\xampp\mysql\data\).
#
# DESIGN & SAFETY CONSTRAINTS:
# 1. Web-Root Exclusion: Drill data, logs, socket, and config live under C:\TopUpers_DrillStaging\ (or custom outside htdocs).
# 2. Production Datadir Zero-Touch: Production data directory is never read, written, or referenced.
# 3. Path Canonicalization: Resolves NTFS junctions, symlinks, and volume mounts. Fails closed on any ambiguity.
# 4. Process & Socket Identity: Tracks exact child PID + StartTimeUtc and validates TCP port ownership before connecting.
# 5. Non-Destructive Failure Handling: Never auto-deletes the drill directory or force-kills ambiguous processes.
# 6. Scope Boundary: Backup restoration and live migration execution are STRICTLY OUT OF SCOPE.
#
# AUTHORIZATION DESIGN (APPROACH A: WINDOWS OS ACCESS CONTROL):
# - SEPARATE LIFECYCLE PATHS:
#     * Provisioning: Requires explicit operator approval ticket (-ConfirmOperatorApproval, -ApprovalTicketFile)
#       validated against protected administrative directory DACLs and atomically consumed in the ledger.
#     * Shutdown: Requires explicit shutdown confirmation (-ConfirmShutdown) and authenticates the running
#       process against the recorded drill session manifest. Does NOT consume a provisioning ticket.
# - OPERATIONAL CLASSIFICATION: The approval-ticket JSON is an OPERATIONAL INTENT ASSERTION,
#   not cryptographic proof of authorization.
# - SECURITY BOUNDARY: The true security boundary is enforced strictly by Windows NTFS DACLs,
#   Security Identifiers (SIDs), and atomic filesystem primitives in C:\TopUpers_AdminAuth\.
# - DIRECTORY SEPARATION:
#     * Tickets: C:\TopUpers_AdminAuth\tickets\
#     * Ledger:  C:\TopUpers_AdminAuth\ledger\
#   These directories MUST be provisioned out-of-band by an authorized administrator with
#   write permissions restricted exclusively to BUILTIN\Administrators (S-1-5-32-544) and SYSTEM (S-1-5-18).
# - ATOMIC SINGLE-USE LEDGER: Tickets require an RFC 4122 UUIDv4 ticket_id and are atomically
#   consumed via [System.IO.File]::Open with FileMode::CreateNew to prevent replay.
#
# KNOWN LIMITATIONS & THREAT MODEL BOUNDARIES:
# 1. CRASH LIMITATION: If the script halts, fails, or crashes AFTER creating the consumption marker
#    but BEFORE provisioning completes, the ticket is permanently consumed. This is fail-closed by
#    design; an administrator must generate a new UUIDv4 ticket for subsequent runs.
# 2. ADMINISTRATOR TAMPERING LIMITATION: Any user running with local administrative privileges
#    (BUILTIN\Administrators, SYSTEM) has the OS-level capability to delete consumption markers,
#    modify NTFS DACLs, or bypass local checks (via SeTakeOwnershipPrivilege / SeRestorePrivilege).
#    This mechanism defends against unprivileged users, non-elevated service accounts, and accidental
#    execution, but cannot constrain a malicious local Administrator.
# 3. REPLAY LIMITATION: Replay is prevented by the atomic consumption marker file. Re-submitting the
#    same ticket_id will always fail with a replay refusal.
# ==============================================================================

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false, HelpMessage = "Path to the backup dump file for sizing calculation.")]
    [string]$DumpPath = "",

    [Parameter(Mandatory = $false, HelpMessage = "Expected SHA-256 hash of the backup dump file.")]
    [string]$DumpSha256 = "",

    [Parameter(Mandatory = $false, HelpMessage = "Dedicated TCP port for the isolated drill daemon (Default: 3308).")]
    [int]$DrillPort = 3308,

    [Parameter(Mandatory = $false, HelpMessage = "Base directory for drill staging (Must be outside C:\xampp\htdocs).")]
    [string]$DrillBaseDir = "C:\TopUpers_DrillStaging",

    # --- Provisioning Authorization Parameters ---
    [Parameter(Mandatory = $false, HelpMessage = "Explicit operator acknowledgement switch required for -Action Provision.")]
    [switch]$ConfirmOperatorApproval = $false,

    [Parameter(Mandatory = $false, HelpMessage = "Path to an external operator authorization ticket JSON file in C:\TopUpers_AdminAuth\tickets\.")]
    [string]$ApprovalTicketFile = "",

    # --- Shutdown Authorization Parameters ---
    [Parameter(Mandatory = $false, HelpMessage = "Explicit operator confirmation switch required for -Action Shutdown.")]
    [switch]$ConfirmShutdown = $false,

    [Parameter(Mandatory = $false, HelpMessage = "Optional explicit path to the drill session manifest JSON file for shutdown verification.")]
    [string]$SessionManifestPath = "",

    # --- Action Selection ---
    [Parameter(Mandatory = $false, HelpMessage = "Action to perform: 'Provision', 'Verify', 'Shutdown', or 'Status'.")]
    [ValidateSet("Provision", "Verify", "Shutdown", "Status")]
    [string]$Action = "Provision"
)

$ErrorActionPreference = "Stop"

# Protected administrative roots for Approach A
$PROTECTED_AUTH_ROOT   = "C:\TopUpers_AdminAuth"
$PROTECTED_TICKETS_DIR = "C:\TopUpers_AdminAuth\tickets"
$PROTECTED_LEDGER_DIR  = "C:\TopUpers_AdminAuth\ledger"

# Well-known trusted administrative Windows SIDs (no hardcoded personal usernames)
$SID_SYSTEM         = "S-1-5-18"             # NT AUTHORITY\SYSTEM
$SID_ADMINISTRATORS = "S-1-5-32-544"         # BUILTIN\Administrators
$ALLOWED_ADMIN_SIDS = @($SID_SYSTEM, $SID_ADMINISTRATORS)

# Production reference paths for boundary enforcement
$prodBaseDir    = "C:\xampp\mysql"
$prodBinDir     = "C:\xampp\mysql\bin"
$prodDataDir    = "C:\xampp\mysql\data"
$prodWebRootDir = "C:\xampp\htdocs"
$prodTemplateDir= "C:\xampp\mysql\backup"

$mysqldExe     = Join-Path $prodBinDir "mysqld.exe"
$mysqladminExe = Join-Path $prodBinDir "mysqladmin.exe"
$mysqlExe      = Join-Path $prodBinDir "mysql.exe"

# ==============================================================================
# SECTION 1: CANONICAL PATH & SECURITY DESCRIPTOR VERIFICATION HELPERS
# ==============================================================================

# [STATIC / RUNTIME CHECK] Resolves canonical Win32 path and validates absence of reparse points.
# NOTE: Inspects reparse-point attributes and resolves Win32 canonical paths at check time.
# While it detects existing junctions/symlinks and fails closed, it does not prevent concurrent
# Win32 filesystem TOCTOU races if an attacker has write access to parent directories.
function Resolve-SafeCanonicalPath {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,
        [Parameter(Mandatory = $false)]
        [bool]$MustExist = $true
    )

    if ($MustExist -and (-not (Test-Path -LiteralPath $Path))) {
        throw "PATH ERROR: Target path does not exist: '$Path'"
    }

    # Normalize path string
    $fullPath = [System.IO.Path]::GetFullPath($Path)

    # Inspect each segment from root to leaf for ReparsePoint attribute (Junctions, Symlinks, Volume Mounts)
    $current = $fullPath
    while ($current -and ($current -ne [System.IO.Path]::GetPathRoot($current))) {
        if (Test-Path -LiteralPath $current) {
            $item = Get-Item -LiteralPath $current -Force
            if ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) {
                # Attempt to resolve link target
                $target = $item.Target
                throw "REPARSE POINT DETECTED: Path segment '$current' is a reparse point/junction pointing to '$target'. Refusing execution (fail-closed)."
            }
        }
        $parent = [System.IO.Path]::GetDirectoryName($current)
        if ($parent -eq $current) { break }
        $current = $parent
    }

    return $fullPath.TrimEnd('\')
}

# [STATIC CHECK] Enforces non-overlap between two canonical directory trees.
function Assert-PathNonOverlap {
    param(
        [Parameter(Mandatory = $true)]
        [string]$CandidatePath,
        [Parameter(Mandatory = $true)]
        [string]$ForbiddenPath,
        [Parameter(Mandatory = $true)]
        [string]$Description
    )

    $cand = $CandidatePath.TrimEnd('\') + '\'
    $forb = $ForbiddenPath.TrimEnd('\') + '\'

    if ($cand.Equals($forb, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "PATH ISOLATION ERROR: Candidate path is identical to forbidden $($Description): '$CandidatePath'"
    }
    if ($cand.StartsWith($forb, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "PATH ISOLATION ERROR: Candidate path resides inside forbidden $($Description): '$CandidatePath'"
    }
    if ($forb.StartsWith($cand, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "PATH ISOLATION ERROR: Forbidden $Description resides inside candidate path: '$ForbiddenPath'"
    }
}

# [RUNTIME CHECK] Enforces that a protected path is owned and writable ONLY by specified trusted SIDs.
# Security Policy:
# 1. Strict Owner Whitelist: Owner SID must strictly be in $AllowedSids.
# 2. Strict Whitelist Model: Prohibits any Deny ACE (fail-closed).
# 3. Explicit Inheritance Protection: When $RequireInheritanceBlocked is true, requires AreAccessRulesProtected.
# 4. Write-Capable ACE Restriction: No trustee outside $AllowedSids may have write-capable permissions.
function Test-TrustedAdminSecurityDescriptor {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,
        [Parameter(Mandatory = $true)]
        [string]$Description,
        [Parameter(Mandatory = $false)]
        [string[]]$AllowedSids = $ALLOWED_ADMIN_SIDS,
        [Parameter(Mandatory = $false)]
        [bool]$RequireInheritanceBlocked = $false
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "SECURITY BOUNDARY ERROR: Protected $Description does not exist at '$Path'. An authorized administrator must provision it."
    }

    $acl = Get-Acl -LiteralPath $Path -ErrorAction Stop

    # 1. Verify Owner SID
    $ownerSid = $acl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
    if ($AllowedSids -notcontains $ownerSid) {
        throw "SECURITY BOUNDARY ERROR: Protected $Description '$Path' is owned by untrusted SID '$ownerSid'. Owner must strictly be in ($($AllowedSids -join ', '))."
    }

    # 2. Verify Inheritance Protection Policy (Fail-Closed)
    if ($RequireInheritanceBlocked -and (-not $acl.AreAccessRulesProtected)) {
        throw "SECURITY BOUNDARY ERROR: Protected $Description '$Path' must have inheritance explicitly blocked (AreAccessRulesProtected is false). Uncontrolled parent inheritance violates security boundary."
    }

    # 3. Inspect Access Control Entries (DACL)
    $writeRightsMask = [System.Security.AccessControl.FileSystemRights]"Write, Modify, Delete, DeleteSubdirectoriesAndFiles, ChangePermissions, TakeOwnership"

    foreach ($rule in $acl.Access) {
        # Strict Fail-Closed Policy: Prohibit any Deny ACE. Deny rules cannot override whitelist model.
        if ($rule.AccessControlType -ne [System.Security.AccessControl.AccessControlType]::Allow) {
            throw "SECURITY BOUNDARY ERROR: Protected $Description '$Path' contains an unapproved ACE type ($($rule.AccessControlType)) for '$($rule.IdentityReference.Value)'. Deny ACEs are prohibited under strict whitelist policy (fail-closed)."
        }

        # Skip container-only inherit rules that grant no access to the current object
        if (($rule.PropagationFlags -band [System.Security.AccessControl.PropagationFlags]::InheritOnly) -ne 0) {
            continue
        }

        $hasWriteRights = ($rule.FileSystemRights -band $writeRightsMask) -ne 0
        if ($hasWriteRights) {
            try {
                $ruleSid = $rule.IdentityReference.Translate([System.Security.Principal.SecurityIdentifier]).Value
            } catch {
                throw "SECURITY BOUNDARY ERROR: Unable to translate trustee '$($rule.IdentityReference.Value)' on '$Path' to a Security Identifier (SID). Failing closed."
            }

            # Fail closed if any trustee other than allowed SIDs has write permissions
            if ($AllowedSids -notcontains $ruleSid) {
                throw "SECURITY BOUNDARY ERROR: Protected $Description '$Path' grants write rights ($($rule.FileSystemRights)) to non-administrative trustee '$($rule.IdentityReference.Value)' (SID: $ruleSid). Failing closed."
            }
        }
    }
}

# [RUNTIME / STATIC CHECK] Discovers and authenticates an active drill session manifest.
# Ensures that:
# 1. Target port cannot be 3306 (Production port).
# 2. Manifest resides in $DrillBaseDir, is owned by trusted SIDs, and has valid schema.
# 3. Manifest references a valid consumed ticket in $PROTECTED_LEDGER_DIR.
# 4. Workspace, config, and data paths are strictly isolated and do not overlap with production.
function Resolve-ActiveDrillSession {
    param(
        [Parameter(Mandatory = $false)]
        [string]$ExplicitManifestPath = "",
        [Parameter(Mandatory = $true)]
        [int]$Port,
        [Parameter(Mandatory = $true)]
        [string]$BaseDir,
        [Parameter(Mandatory = $true)]
        [string]$ActionName
    )

    if ($Port -eq 3306) {
        throw "PORT ERROR: Drill port cannot be 3306 (Production port). Refusing $ActionName."
    }

    $currentRunnerSid   = [System.Security.Principal.WindowsIdentity]::GetCurrent().User.Value
    $trustedSids        = @($currentRunnerSid, $SID_SYSTEM, $SID_ADMINISTRATORS)
    $drillBaseCanonical = Resolve-SafeCanonicalPath -Path $BaseDir -MustExist $true
    $uuidV4Pattern      = '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$'

    $resolvedManifestPath = ""

    if (-not [string]::IsNullOrWhiteSpace($ExplicitManifestPath)) {
        if (-not (Test-Path -LiteralPath $ExplicitManifestPath)) {
            throw "$ActionName ERROR: Specified session manifest file does not exist: '$ExplicitManifestPath'"
        }
        $resolvedManifestPath = Resolve-SafeCanonicalPath -Path $ExplicitManifestPath -MustExist $true
    } else {
        # Discover candidate manifests in drill staging directory
        $candidateManifests = Get-ChildItem -Path (Join-Path $drillBaseCanonical "mariadb_drill_*\drill_session.json") -File -ErrorAction SilentlyContinue

        $matchingManifests = @()
        foreach ($cand in $candidateManifests) {
            try {
                $candCanonical = Resolve-SafeCanonicalPath -Path $cand.FullName -MustExist $true

                # 1. Pre-filter candidates: file and parent directory must be owned by trusted SIDs
                $candAcl = Get-Acl -LiteralPath $candCanonical -ErrorAction Stop
                $candOwnerSid = $candAcl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
                if ($trustedSids -notcontains $candOwnerSid) {
                    continue
                }

                $candDir = Split-Path -Parent $candCanonical
                $candDirParent = Split-Path -Parent $candDir
                if (-not $candDirParent.Equals($drillBaseCanonical, [System.StringComparison]::OrdinalIgnoreCase)) {
                    continue
                }

                $candDirAcl = Get-Acl -LiteralPath $candDir -ErrorAction Stop
                $candDirOwnerSid = $candDirAcl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
                if ($trustedSids -notcontains $candDirOwnerSid) {
                    continue
                }

                # 2. Parse candidate manifest
                $content = Get-Content -LiteralPath $candCanonical -Raw -ErrorAction Stop | ConvertFrom-Json -ErrorAction Stop
                if ($content.drill_port -eq $Port -and ($content.status -in @("RUNNING", "PROVISIONAL", "VERIFICATION_FAILED_MANUAL_INTERVENTION_REQUIRED"))) {
                    # 3. Provenance check: must reference an existing marker in protected ledger
                    if ($content.ticket_id -and ($content.ticket_id -match $uuidV4Pattern)) {
                        $ledgerMarker = Join-Path $PROTECTED_LEDGER_DIR "$($content.ticket_id).consumed"
                        if (Test-Path -LiteralPath $ledgerMarker) {
                            $matchingManifests += $candCanonical
                        }
                    }
                }
            } catch {
                # Ignore unreadable / malformed / untrusted candidates
            }
        }

        if ($matchingManifests.Count -eq 0) {
            return $null
        }

        if ($matchingManifests.Count -gt 1) {
            throw "AMBIGUOUS SESSION ERROR: Multiple active session manifests found for port $Port ($($matchingManifests.Count)). Specify -SessionManifestPath explicitly."
        }

        $resolvedManifestPath = Resolve-SafeCanonicalPath -Path $matchingManifests[0] -MustExist $true
    }

    # Enforce non-overlap against production
    $canonicalWebRootCheck = Resolve-SafeCanonicalPath -Path $prodWebRootDir -MustExist $true
    $canonicalProdDataCheck= Resolve-SafeCanonicalPath -Path $prodDataDir -MustExist $true
    $canonicalProdBaseCheck= Resolve-SafeCanonicalPath -Path $prodBaseDir -MustExist $true

    Assert-PathNonOverlap -CandidatePath $resolvedManifestPath -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $resolvedManifestPath -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"

    # Verify session manifest security descriptor
    Test-TrustedAdminSecurityDescriptor -Path $resolvedManifestPath -Description "Session Manifest" -AllowedSids $trustedSids

    # Parse and validate session manifest schema
    try {
        $sessionContent = Get-Content -LiteralPath $resolvedManifestPath -Raw -ErrorAction Stop
        $session = $sessionContent | ConvertFrom-Json -ErrorAction Stop
    } catch {
        throw "$ActionName ERROR: Failed to parse session manifest JSON: $_"
    }

    if ($session.drill_port -ne $Port) {
        throw "$ActionName ERROR: Session manifest port ($($session.drill_port)) does not match target port ($Port)."
    }

    $expectedPid       = [int]$session.child_pid
    $expectedStartUtc  = [DateTime]::Parse($session.start_time_utc).ToUniversalTime()
    $expectedWorkspace = Resolve-SafeCanonicalPath -Path $session.drill_workspace -MustExist $true
    $expectedIni       = Resolve-SafeCanonicalPath -Path $session.drill_ini -MustExist $true

    Assert-PathNonOverlap -CandidatePath $expectedWorkspace -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $expectedWorkspace -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $expectedIni -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $expectedIni -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"

    # Enforce workspace resides directly inside drill base directory
    $expectedWorkspaceParent = Split-Path -Parent $expectedWorkspace
    if (-not $expectedWorkspaceParent.Equals($drillBaseCanonical, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "SECURITY REFUSAL: Recorded workspace '$expectedWorkspace' does not reside directly in drill base directory '$drillBaseCanonical'."
    }

    # Strict containment checks: manifest and drill_ini must reside strictly inside recorded workspace
    $expectedManifestCanonical = Resolve-SafeCanonicalPath -Path (Join-Path $expectedWorkspace "drill_session.json") -MustExist $true
    if (-not $resolvedManifestPath.Equals($expectedManifestCanonical, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "SECURITY REFUSAL: Session manifest '$resolvedManifestPath' does not match canonical workspace manifest '$expectedManifestCanonical'."
    }

    $expectedIniCanonical = Resolve-SafeCanonicalPath -Path (Join-Path (Join-Path $expectedWorkspace "config") "drill_my.ini") -MustExist $true
    if (-not $expectedIni.Equals($expectedIniCanonical, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "SECURITY REFUSAL: Recorded drill_ini '$expectedIni' does not match canonical workspace configuration '$expectedIniCanonical'."
    }

    # Verify workspace directory and option file security descriptors
    Test-TrustedAdminSecurityDescriptor -Path $expectedWorkspace -Description "Drill Workspace Directory" -AllowedSids $trustedSids
    Test-TrustedAdminSecurityDescriptor -Path $expectedIni -Description "Isolated Option File" -AllowedSids $trustedSids

    # Inspect isolated option file content
    $iniRawContent = Get-Content -LiteralPath $expectedIni -Raw -ErrorAction Stop
    if ($iniRawContent -match "mysql[\\/]data" -or $iniRawContent -match "\b3306\b") {
        throw "SECURITY VIOLATION: Option file '$expectedIni' references production port 3306 or production datadir! Refusing $ActionName."
    }
    if ($iniRawContent -notmatch "(?m)^\s*port\s*=\s*$Port\b") {
        throw "SECURITY VIOLATION: Option file '$expectedIni' does not configure dedicated drill port $Port! Refusing $ActionName."
    }
    $normWorkspaceForIni = $expectedWorkspace.Replace('\', '/')
    if ($iniRawContent -notmatch [regex]::Escape($normWorkspaceForIni)) {
        throw "SECURITY VIOLATION: Option file '$expectedIni' does not reference recorded workspace path '$normWorkspaceForIni'! Refusing $ActionName."
    }

    # Validate session ticket_id provenance against protected administrative ledger
    if (-not $session.ticket_id -or ($session.ticket_id -notmatch $uuidV4Pattern)) {
        throw "SECURITY REFUSAL: Session manifest is missing a valid RFC 4122 UUIDv4 'ticket_id'."
    }

    $canonicalLedgerDirCheck = Resolve-SafeCanonicalPath -Path $PROTECTED_LEDGER_DIR -MustExist $true
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDirCheck -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDirCheck -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDirCheck -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    $expectedLedgerMarker = Join-Path $canonicalLedgerDirCheck "$($session.ticket_id).consumed"
    if (-not (Test-Path -LiteralPath $expectedLedgerMarker)) {
        throw "SECURITY REFUSAL: Session ticket_id '$($session.ticket_id)' does not exist in protected ledger '$canonicalLedgerDirCheck'. Untrusted session manifest!"
    }

    Assert-PathNonOverlap -CandidatePath $expectedLedgerMarker -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $expectedLedgerMarker -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $expectedLedgerMarker -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    Test-TrustedAdminSecurityDescriptor -Path $expectedLedgerMarker -Description "Session Ledger Marker" -RequireInheritanceBlocked $true

    try {
        $ledgerRecord = Get-Content -LiteralPath $expectedLedgerMarker -Raw | ConvertFrom-Json
        if ($ledgerRecord.ticket_id -ne $session.ticket_id) {
            throw "SECURITY REFUSAL: Ledger marker ticket_id mismatch."
        }
        if ($ledgerRecord.target_port -ne $Port) {
            throw "SECURITY REFUSAL: Ledger marker target_port ($($ledgerRecord.target_port)) does not match configured drill port ($Port)."
        }
    } catch {
        throw "SECURITY REFUSAL: Failed to validate protected ledger marker record: $_"
    }

    return [PSCustomObject]@{
        ManifestPath      = $resolvedManifestPath
        Session           = $session
        ExpectedPid       = $expectedPid
        ExpectedStartUtc  = $expectedStartUtc
        ExpectedWorkspace = $expectedWorkspace
        ExpectedIni       = $expectedIni
    }
}


# ==============================================================================
# SECTION 2: ISOLATED CONFIGURATION GENERATION HELPER (drill_my.ini)
# ==============================================================================
# [STATIC CHECK] Generates dedicated option file for MariaDB 10.4.32.
# Uses forward slashes in MySQL configuration file as required by MariaDB on Windows.
#
# MARIADB 10.4.32 OPTION COMPATIBILITY AUDIT:
# - 'innodb_temp_tablespaces_dir': EXCLUDED. This is a MySQL 8.0-specific option; it does not exist
#   in MariaDB 10.4 and causes a fatal server initialization error if passed.
# - In MariaDB 10.4, InnoDB temporary tablespace (ibtmp1) defaults to 'innodb_data_home_dir'.
# - General server temporary files and disk-based temp tables are governed by 'tmpdir'.
function Generate-IsolatedIniContent {
    param(
        [string]$DataDir,
        [string]$TmpDir,
        [string]$LogDir,
        [int]$Port
    )

    $dataDirNorm = $DataDir.Replace('\', '/')
    $tmpDirNorm  = $TmpDir.Replace('\', '/')
    $logDirNorm  = $LogDir.Replace('\', '/')

    $iniContent = @"
# ==============================================================================
# TOPUPERS GATE 2B -- EPHEMERAL ISOLATED MARIADB 10.4.32 DRILL CONFIGURATION
# THIS FILE IS STRICTLY DEDICATED TO DRILL PORT $Port
# ==============================================================================

[mysqld]
port = $Port
bind-address = 127.0.0.1
datadir = "$dataDirNorm"
tmpdir = "$tmpDirNorm"
innodb_data_home_dir = "$dataDirNorm"
innodb_log_group_home_dir = "$dataDirNorm"
innodb_undo_directory = "$dataDirNorm"
aria_log_dir_path = "$dataDirNorm"
log-error = "$logDirNorm/mariadb_drill_err.log"
pid-file = "$logDirNorm/mariadb_drill.pid"
secure_file_priv = "$tmpDirNorm"

# Operational bounds
general_log = 0
slow_query_log = 0
skip-name-resolve = 1
skip-networking = 0
default_storage_engine = InnoDB
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Memory limits for lightweight drill
innodb_buffer_pool_size = 64M
innodb_log_file_size = 16M

[client]
port = $Port
host = 127.0.0.1

[mysqladmin]
port = $Port
host = 127.0.0.1
"@
    return $iniContent
}

# ==============================================================================
# SCRIPT BANNER & TOP-LEVEL STATUS
# ==============================================================================
Write-Host "======================================================================"
Write-Host "TopUpers Gate 2B -- Isolated MariaDB 10.4.32 Drill Daemon Provisioner"
Write-Host "Action Selected: $Action"
Write-Host "Current Status:  HOLD (Non-executed safety-guarded implementation)"
Write-Host "======================================================================"

# [FAIL-CLOSED GUARD] Refuse unknown actions explicitly with non-zero exit
if ($Action -notin @("Provision", "Status", "Verify", "Shutdown")) {
    Write-Error "ACTION ERROR: Unrecognized action '$Action'. Refusing execution."
    exit 2
}

# ==============================================================================
# LIFECYCLE BRANCH 1: PROVISIONING ORCHESTRATION (FAIL-CLOSED)
# ==============================================================================
if ($Action -eq "Provision") {

    # --------------------------------------------------------------------------
    # STEP 1: PROVISIONING AUTHORIZATION GUARD (TICKET & LEDGER)
    # --------------------------------------------------------------------------
    Write-Host "`n[PHASE 0] Validating Provisioning Authorization Guard..."

    if (-not $ConfirmOperatorApproval -or [string]::IsNullOrWhiteSpace($ApprovalTicketFile)) {
        Write-Warning "CRITICAL EXECUTION REFUSAL: Provisioning authorization guard is active."
        Write-Warning "This action requires an external, separately supplied operator authorization ticket in '$PROTECTED_TICKETS_DIR'."
        Write-Warning "Required parameters: -Action Provision -ConfirmOperatorApproval -ApprovalTicketFile '<path_to_ticket.json>'"
        Write-Warning "No embedded secret token exists; an external administrative ticket must be explicitly provided."
        Write-Warning "No directories created, no binaries launched, no database connections opened."
        Write-Host "Result: REFUSED (HOLD)" -ForegroundColor Yellow
        exit 2
    }

    # Verify existence of administrative protected directories (MUST NOT BE CREATED BY THIS SCRIPT)
    if (-not (Test-Path -LiteralPath $PROTECTED_AUTH_ROOT)) {
        Write-Error "AUTHORIZATION ERROR: Protected administrative root '$PROTECTED_AUTH_ROOT' does not exist."
        Write-Error "An authorized administrator must separately provision this directory with restricted ACLs."
        exit 2
    }
    if (-not (Test-Path -LiteralPath $PROTECTED_TICKETS_DIR)) {
        Write-Error "AUTHORIZATION ERROR: Protected tickets directory '$PROTECTED_TICKETS_DIR' does not exist."
        Write-Error "An authorized administrator must separately provision this directory with restricted ACLs."
        exit 2
    }
    if (-not (Test-Path -LiteralPath $PROTECTED_LEDGER_DIR)) {
        Write-Error "AUTHORIZATION ERROR: Protected ledger directory '$PROTECTED_LEDGER_DIR' does not exist."
        Write-Error "An authorized administrator must separately provision this directory with restricted ACLs."
        exit 2
    }

    # Verify security descriptors of protected administrative directories
    Write-Host "  Verifying administrative security boundaries on authorization directories..."
    try {
        Test-TrustedAdminSecurityDescriptor -Path $PROTECTED_AUTH_ROOT -Description "Authorization Root Directory" -RequireInheritanceBlocked $true
        Test-TrustedAdminSecurityDescriptor -Path $PROTECTED_TICKETS_DIR -Description "Tickets Directory" -RequireInheritanceBlocked $true
        Test-TrustedAdminSecurityDescriptor -Path $PROTECTED_LEDGER_DIR -Description "Ledger Directory" -RequireInheritanceBlocked $true
        Write-Host "  [OK] Administrative directory security descriptors verified."
    } catch {
        Write-Error "AUTHORIZATION SECURITY FAILURE: $_"
        exit 2
    }

    # Validate ticket file existence and canonical location
    if (-not (Test-Path -LiteralPath $ApprovalTicketFile)) {
        Write-Error "AUTHORIZATION ERROR: Operator approval ticket file not found: '$ApprovalTicketFile'"
        exit 2
    }

    $canonicalTicketPath   = Resolve-SafeCanonicalPath -Path $ApprovalTicketFile -MustExist $true
    $canonicalTicketsDir   = Resolve-SafeCanonicalPath -Path $PROTECTED_TICKETS_DIR -MustExist $true
    $canonicalAuthRoot     = Resolve-SafeCanonicalPath -Path $PROTECTED_AUTH_ROOT -MustExist $true
    $canonicalLedgerDir    = Resolve-SafeCanonicalPath -Path $PROTECTED_LEDGER_DIR -MustExist $true
    $canonicalWebRootCheck = Resolve-SafeCanonicalPath -Path $prodWebRootDir -MustExist $true
    $canonicalProdDataCheck= Resolve-SafeCanonicalPath -Path $prodDataDir -MustExist $true
    $canonicalProdBaseCheck= Resolve-SafeCanonicalPath -Path $prodBaseDir -MustExist $true

    # Auth root, tickets dir, and ledger dir must reside outside web root and outside production database directories
    Assert-PathNonOverlap -CandidatePath $canonicalAuthRoot -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $canonicalAuthRoot -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $canonicalAuthRoot -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    Assert-PathNonOverlap -CandidatePath $canonicalTicketsDir -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $canonicalTicketsDir -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $canonicalTicketsDir -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDir -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDir -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDir -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    # Ticket file must reside directly inside the protected tickets directory
    $ticketParent = [System.IO.Path]::GetDirectoryName($canonicalTicketPath)
    if (-not $ticketParent.Equals($canonicalTicketsDir, [System.StringComparison]::OrdinalIgnoreCase)) {
        Write-Error "AUTHORIZATION ERROR: Ticket file must reside directly inside '$canonicalTicketsDir'. Given path: '$canonicalTicketPath'"
        exit 2
    }

    # Ticket file must reside outside web root and outside production database directory
    Assert-PathNonOverlap -CandidatePath $canonicalTicketPath -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $canonicalTicketPath -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $canonicalTicketPath -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    # Verify security descriptor of the ticket file itself
    try {
        Test-TrustedAdminSecurityDescriptor -Path $canonicalTicketPath -Description "Ticket File"
        Write-Host "  [OK] Ticket file security descriptor verified (Owned and writable strictly by Administrators/SYSTEM)."
    } catch {
        Write-Error "AUTHORIZATION SECURITY FAILURE: $_"
        exit 2
    }

    # Parse and validate ticket structure (Operational Intent Assertion)
    try {
        $ticketContent = Get-Content -LiteralPath $canonicalTicketPath -Raw -ErrorAction Stop
        $ticket = $ticketContent | ConvertFrom-Json -ErrorAction Stop
    } catch {
        Write-Error "AUTHORIZATION ERROR: Failed to parse operator approval ticket JSON: $_"
        exit 2
    }

    # Validate UUIDv4 Ticket ID
    $uuidV4Pattern = '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$'
    if (-not $ticket.ticket_id -or ($ticket.ticket_id -notmatch $uuidV4Pattern)) {
        Write-Error "AUTHORIZATION ERROR: Ticket 'ticket_id' is missing or not a valid RFC 4122 UUIDv4 (Got '$($ticket.ticket_id)')."
        exit 2
    }

    if (-not $ticket.operator_name -or [string]::IsNullOrWhiteSpace($ticket.operator_name)) {
        Write-Error "AUTHORIZATION ERROR: Ticket is missing valid 'operator_name'."
        exit 2
    }
    if ($ticket.authorized_action -ne "PROVISION_ISOLATED_MARIADB_DRILL") {
        Write-Error "AUTHORIZATION ERROR: Ticket 'authorized_action' mismatch. Expected 'PROVISION_ISOLATED_MARIADB_DRILL', got '$($ticket.authorized_action)'."
        exit 2
    }
    if ($ticket.target_port -ne $DrillPort) {
        Write-Error "AUTHORIZATION ERROR: Ticket target port ($($ticket.target_port)) does not match configured drill port ($DrillPort)."
        exit 2
    }
    if ($ticket.environment -ne "RECHAX_DRILL") {
        Write-Error "AUTHORIZATION ERROR: Ticket environment mismatch. Expected 'RECHAX_DRILL', got '$($ticket.environment)'."
        exit 2
    }

    # Validate ticket validity window (maximum 15 minutes validity from creation)
    try {
        $ticketTimeUtc = [DateTime]::Parse($ticket.timestamp_utc).ToUniversalTime()
        $nowUtc = [DateTime]::UtcNow
        $ticketAgeSeconds = ($nowUtc - $ticketTimeUtc).TotalSeconds
        if ($ticketAgeSeconds -lt -60 -or $ticketAgeSeconds -gt 900) {
            Write-Error "AUTHORIZATION ERROR: Operator ticket is expired or has an invalid timestamp (Age: $([math]::Round($ticketAgeSeconds, 0))s, Max allowed: 900s)."
            exit 2
        }
    } catch {
        Write-Error "AUTHORIZATION ERROR: Invalid or missing 'timestamp_utc' in operator ticket: $_"
        exit 2
    }

    # Atomic Single-Use Ledger Consumption Marker
    $canonicalLedgerDir = Resolve-SafeCanonicalPath -Path $PROTECTED_LEDGER_DIR -MustExist $true
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDir -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDir -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $canonicalLedgerDir -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    $markerFileName     = "$($ticket.ticket_id).consumed"
    $markerFilePath     = Join-Path $canonicalLedgerDir $markerFileName
    Assert-PathNonOverlap -CandidatePath $markerFilePath -ForbiddenPath $canonicalWebRootCheck -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $markerFilePath -ForbiddenPath $canonicalProdDataCheck -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $markerFilePath -ForbiddenPath $canonicalProdBaseCheck -Description "Production MariaDB Base"

    Write-Host "  Consuming ticket atomically in durable ledger..."
    try {
        # Open with FileMode::CreateNew ensures atomic creation; fails if file already exists
        $fileStream = [System.IO.File]::Open(
            $markerFilePath,
            [System.IO.FileMode]::CreateNew,
            [System.IO.FileAccess]::Write,
            [System.IO.FileShare]::None
        )
    } catch [System.IO.IOException] {
        Write-Error "REPLAY REFUSAL: Ticket ID '$($ticket.ticket_id)' has already been consumed in the durable ledger! Marker exists at '$markerFilePath'."
        exit 2
    } catch {
        Write-Error "LEDGER ERROR: Failed to create atomic consumption marker at '$markerFilePath': $_"
        exit 2
    }

    try {
        $currentIdentity = [System.Security.Principal.WindowsIdentity]::GetCurrent()
        $markerRecord = [ordered]@{
            "ticket_id"        = $ticket.ticket_id
            "consumed_at_utc"  = [DateTime]::UtcNow.ToString("o")
            "runner_pid"       = $PID
            "runner_user"      = $currentIdentity.Name
            "runner_user_sid"  = $currentIdentity.User.Value
            "operator_name"    = $ticket.operator_name
            "target_port"      = $DrillPort
            "ticket_timestamp" = $ticket.timestamp_utc
        }
        $markerJson = $markerRecord | ConvertTo-Json -Depth 4
        $writer = [System.IO.StreamWriter]::new($fileStream, [System.Text.Encoding]::UTF8)
        $writer.Write($markerJson)
        $writer.Flush()
    } finally {
        $fileStream.Close()
        $fileStream.Dispose()
    }

    # Explicitly establish and verify strict administrative ownership and DACL on consumed ledger marker
    try {
        $markerAcl = Get-Acl -LiteralPath $markerFilePath -ErrorAction Stop
        $markerOwnerSid = $markerAcl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
        if ($markerOwnerSid -ne $SID_ADMINISTRATORS) {
            $adminSid = [System.Security.Principal.SecurityIdentifier]::new($SID_ADMINISTRATORS)
            $markerAcl.SetOwner($adminSid)
            # Enforce inheritance protection and convert inherited rules to explicit rules
            $markerAcl.SetAccessRuleProtection($true, $true)
            Set-Acl -LiteralPath $markerFilePath -AclObject $markerAcl -ErrorAction Stop
        }

        # Re-read ACL fresh from disk and strictly verify owner, inheritance state, Deny ACE absence, and write-capable ACEs
        Test-TrustedAdminSecurityDescriptor -Path $markerFilePath -Description "Consumed Ledger Marker" -RequireInheritanceBlocked $true
        Write-Host "  [OK] Ledger marker ownership (*$SID_ADMINISTRATORS) and DACL verified from disk."
    } catch {
        Write-Error "LEDGER INTEGRITY FAILURE: Failed to establish or verify administrative security descriptor on '$markerFilePath': $_"
        exit 2
    }

    Write-Host "  [OK] Operator approval ticket verified: Approved by '$($ticket.operator_name)' at $($ticketTimeUtc.ToString('o'))"
    Write-Host "  [OK] Ticket '$($ticket.ticket_id)' atomically consumed in ledger: '$markerFilePath'"

    # --------------------------------------------------------------------------
    # STEP 2: PREFLIGHT SYSTEM & BINARY VALIDATION
    # --------------------------------------------------------------------------
    Write-Host "`n[PHASE 1] Preflight System & Binary Validation..."

    $requiredBinaries = @($mysqldExe, $mysqladminExe, $mysqlExe)
    foreach ($bin in $requiredBinaries) {
        if (-not (Test-Path -LiteralPath $bin)) {
            throw "PREFLIGHT ERROR: Required MariaDB binary not found: '$bin'"
        }
        $binItem = Get-Item -LiteralPath $bin
        if ($binItem.Length -le 0) {
            throw "PREFLIGHT ERROR: MariaDB binary is empty (0 bytes): '$bin'"
        }
        Write-Host "  [OK] Verified binary: '$bin' ($([math]::Round($binItem.Length / 1MB, 2)) MB)"
    }

    # Verify clean backup template directory structure
    if (-not (Test-Path -LiteralPath $prodTemplateDir)) {
        throw "PREFLIGHT ERROR: MariaDB backup template directory not found: '$prodTemplateDir'"
    }
    $templateMysqlDir = Join-Path $prodTemplateDir "mysql"
    if (-not (Test-Path -LiteralPath $templateMysqlDir)) {
        throw "PREFLIGHT ERROR: Clean template is missing mandatory system directory: '$templateMysqlDir'"
    }
    $templateFiles = Get-ChildItem -LiteralPath $prodTemplateDir -Recurse -File
    $templateTotalBytes = ($templateFiles | Measure-Object -Property Length -Sum).Sum
    if ($templateTotalBytes -lt 10MB) {
        throw "PREFLIGHT ERROR: Clean template total size is suspiciously small ($templateTotalBytes bytes < 10MB). Aborting."
    }
    Write-Host "  [OK] Verified clean template: '$prodTemplateDir' ($($templateFiles.Count) files, $([math]::Round($templateTotalBytes / 1MB, 2)) MB)"

    # Port boundary sanity
    if ($DrillPort -eq 3306) {
        throw "PREFLIGHT ERROR: Drill port cannot be 3306 (Production port). Refusing configuration."
    }
    if ($DrillPort -lt 1024 -or $DrillPort -gt 65535) {
        throw "PREFLIGHT ERROR: Invalid drill port number: $DrillPort (must be between 1024 and 65535)."
    }

    # Dual-stack port listener pre-check
    Write-Host "  Checking for port conflicts on port $DrillPort..."
    $activeListeners = Get-NetTCPConnection -LocalPort $DrillPort -ErrorAction SilentlyContinue
    if ($activeListeners) {
        $conflicts = ($activeListeners | ForEach-Object { "$($_.LocalAddress):$($_.LocalPort) (PID $($_.OwningProcess))" }) -join ", "
        throw "PREFLIGHT ERROR: Target drill port $DrillPort is already in use by: $conflicts"
    }
    Write-Host "  [OK] Port $DrillPort is completely free (no active TCP listeners)."

    # Production port 3306 status check
    $prodListeners = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
    if ($prodListeners) {
        Write-Host "  [NOTE] Production port 3306 is active (Listening). Strict boundary isolation will be enforced."
    } else {
        Write-Host "  [NOTE] Production port 3306 is currently inactive."
    }

    # --------------------------------------------------------------------------
    # STEP 3: DUMP SIZING & DISK SPACE VALIDATION
    # --------------------------------------------------------------------------
    Write-Host "`n[PHASE 2] Dump Sizing & Disk Space Validation..."

    $uncompressedDumpBytes = 0

    if ($DumpPath -ne "") {
        if (-not (Test-Path -LiteralPath $DumpPath)) {
            throw "SIZING ERROR: Specified dump file does not exist: '$DumpPath'"
        }
        $dumpItem = Get-Item -LiteralPath $DumpPath
        if ($dumpItem.Length -le 0) {
            throw "SIZING ERROR: Dump file is empty (0 bytes): '$DumpPath'"
        }

        if ($DumpPath.EndsWith(".sql", [System.StringComparison]::OrdinalIgnoreCase)) {
            $uncompressedDumpBytes = $dumpItem.Length
            Write-Host "  [OK] Plain text SQL dump detected. Size: $([math]::Round($uncompressedDumpBytes / 1MB, 2)) MB"
        } else {
            throw "SIZING ERROR: Compressed dump files require verified manifest uncompressed_byte_size. Automatic estimation is prohibited."
        }

        if ($DumpSha256 -ne "") {
            Write-Host "  Validating dump SHA-256 integrity..."
            $actualSha256 = (Get-FileHash -LiteralPath $DumpPath -Algorithm SHA256).Hash
            if (-not $actualSha256.Equals($DumpSha256, [System.StringComparison]::OrdinalIgnoreCase)) {
                throw "INTEGRITY ERROR: Dump SHA-256 mismatch! Expected: $DumpSha256, Actual: $actualSha256"
            }
            Write-Host "  [OK] Dump SHA-256 hash verified: $actualSha256"
        }
    } else {
        Write-Host "  [NOTE] No dump path supplied. Using baseline sizing allocation (100 MB dump estimate)."
        $uncompressedDumpBytes = 100MB
    }

    $headroomBytes = 500MB
    $requiredFreeBytes = $templateTotalBytes + ($uncompressedDumpBytes * 3) + $headroomBytes

    Write-Host "  Required Free Space Calculation:"
    Write-Host "    - Template Size:       $([math]::Round($templateTotalBytes / 1MB, 2)) MB"
    Write-Host "    - Uncompressed Dump:   $([math]::Round($uncompressedDumpBytes / 1MB, 2)) MB (x3: $([math]::Round(($uncompressedDumpBytes * 3) / 1MB, 2)) MB)"
    Write-Host "    - Safety Headroom:     $([math]::Round($headroomBytes / 1MB, 2)) MB"
    Write-Host "    - Total Required:      $([math]::Round($requiredFreeBytes / 1MB, 2)) MB ($([math]::Round($requiredFreeBytes / 1GB, 2)) GB)"

    $drillBaseCanonical = Resolve-SafeCanonicalPath -Path $DrillBaseDir -MustExist $true
    $targetDriveRoot    = [System.IO.Path]::GetPathRoot($drillBaseCanonical)
    $driveInfo          = [System.IO.DriveInfo]::new($targetDriveRoot)
    $availableFreeBytes = $driveInfo.AvailableFreeSpace

    Write-Host "  Available Space on $targetDriveRoot : $([math]::Round($availableFreeBytes / 1GB, 2)) GB"

    if ($availableFreeBytes -lt $requiredFreeBytes) {
        throw "DISK SPACE ERROR: Insufficient disk space on drive $targetDriveRoot. Required: $([math]::Round($requiredFreeBytes / 1MB, 2)) MB, Available: $([math]::Round($availableFreeBytes / 1MB, 2)) MB"
    }
    Write-Host "  [OK] Disk space check passed."

    # --------------------------------------------------------------------------
    # STEP 4: PATH ISOLATION, SECURITY DESCRIPTOR & WORKSPACE SETUP
    # --------------------------------------------------------------------------
    Write-Host "`n[PHASE 3] Path Isolation, Security Descriptor & Workspace Validation..."

    $currentRunnerSid      = [System.Security.Principal.WindowsIdentity]::GetCurrent().User.Value
    $provisionTrustedSids  = @($currentRunnerSid, $SID_SYSTEM, $SID_ADMINISTRATORS)
    $SID_TRUSTED_INSTALLER = "S-1-5-80-956008885-3418522649-1831038044-1853292631-2271478464"
    $parentAllowedSids     = @($currentRunnerSid, $SID_SYSTEM, $SID_ADMINISTRATORS, $SID_TRUSTED_INSTALLER)

    # 1. Enforce strict administrative ACLs on staging root directory
    Write-Host "  Verifying security descriptor of staging root directory..."
    Test-TrustedAdminSecurityDescriptor -Path $drillBaseCanonical -Description "Staging Base Directory" -AllowedSids $provisionTrustedSids -RequireInheritanceBlocked $true

    # 2. Enforce strict administrative ACLs on relevant intermediate parent directories
    Write-Host "  Verifying security descriptors of staging parent directories..."
    $parentCursor = [System.IO.Path]::GetDirectoryName($drillBaseCanonical)
    while ($parentCursor -and ($parentCursor -ne [System.IO.Path]::GetPathRoot($parentCursor))) {
        if (Test-Path -LiteralPath $parentCursor) {
            Test-TrustedAdminSecurityDescriptor -Path $parentCursor -Description "Staging Parent Directory ($parentCursor)" -AllowedSids $parentAllowedSids
        }
        $nextParent = [System.IO.Path]::GetDirectoryName($parentCursor)
        if ($nextParent -eq $parentCursor) { break }
        $parentCursor = $nextParent
    }

    $canonicalProdData   = Resolve-SafeCanonicalPath -Path $prodDataDir -MustExist $true
    $canonicalWebRoot    = Resolve-SafeCanonicalPath -Path $prodWebRootDir -MustExist $true
    $canonicalProdBase   = Resolve-SafeCanonicalPath -Path $prodBaseDir -MustExist $true

    Assert-PathNonOverlap -CandidatePath $drillBaseCanonical -ForbiddenPath $canonicalWebRoot -Description "Web Root ($canonicalWebRoot)"
    Assert-PathNonOverlap -CandidatePath $drillBaseCanonical -ForbiddenPath $canonicalProdData -Description "Production Data ($canonicalProdData)"

    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $runId     = [System.Guid]::NewGuid().ToString("N").Substring(0, 8)
    $drillWorkspaceDir = Join-Path $drillBaseCanonical "mariadb_drill_${timestamp}_${runId}"

    $drillDataDir   = Join-Path $drillWorkspaceDir "data"
    $drillTmpDir    = Join-Path $drillWorkspaceDir "tmp"
    $drillLogDir    = Join-Path $drillWorkspaceDir "logs"
    $drillConfigDir = Join-Path $drillWorkspaceDir "config"
    $drillIniFile   = Join-Path $drillConfigDir "drill_my.ini"

    Assert-PathNonOverlap -CandidatePath $drillWorkspaceDir -ForbiddenPath $canonicalWebRoot -Description "Web Root"
    Assert-PathNonOverlap -CandidatePath $drillWorkspaceDir -ForbiddenPath $canonicalProdData -Description "Production Data"
    Assert-PathNonOverlap -CandidatePath $drillWorkspaceDir -ForbiddenPath $canonicalProdBase -Description "Production MariaDB Base"

    Write-Host "  [OK] Drill workspace isolated path: '$drillWorkspaceDir'"
    Write-Host "  [OK] Path non-overlap checks against production data and web root verified."

    # --------------------------------------------------------------------------
    # STEP 5: WORKSPACE PROVISIONING & CLEAN TEMPLATE COPY
    # --------------------------------------------------------------------------
    Write-Host "`n[PHASE 4] Provisioning Drill Workspace & Clean Template..."

    New-Item -ItemType Directory -Path $drillWorkspaceDir -Force | Out-Null
    New-Item -ItemType Directory -Path $drillDataDir -Force | Out-Null
    New-Item -ItemType Directory -Path $drillTmpDir -Force | Out-Null
    New-Item -ItemType Directory -Path $drillLogDir -Force | Out-Null
    New-Item -ItemType Directory -Path $drillConfigDir -Force | Out-Null

    # Validate workspace directory security descriptor before populating template or files
    Test-TrustedAdminSecurityDescriptor -Path $drillWorkspaceDir -Description "Drill Workspace Directory" -AllowedSids $provisionTrustedSids

    Write-Host "  Copying clean MariaDB system template to drill data directory..."
    Copy-Item -Path (Join-Path $prodTemplateDir "*") -Destination $drillDataDir -Recurse -Force
    Write-Host "  [OK] Clean template copied successfully."

    $iniText = Generate-IsolatedIniContent -DataDir $drillDataDir -TmpDir $drillTmpDir -LogDir $drillLogDir -Port $DrillPort
    Set-Content -LiteralPath $drillIniFile -Value $iniText -Encoding ASCII

    # Validate option file security descriptor before proceeding to process launch
    Test-TrustedAdminSecurityDescriptor -Path $drillIniFile -Description "Isolated Option File" -AllowedSids $provisionTrustedSids

    $writtenIni = Get-Content -LiteralPath $drillIniFile -Raw
    if ($writtenIni -match "mysql/data" -or $writtenIni -match "htdocs" -or $writtenIni -match "3306") {
        throw "SECURITY VIOLATION: Generated drill_my.ini contains forbidden production references! Aborting."
    }
    Write-Host "  [OK] Isolated configuration written and verified: '$drillIniFile'"

    # --------------------------------------------------------------------------
    # STEP 6: PROCESS LAUNCH & IDENTITY TRACKING
    # --------------------------------------------------------------------------
    Write-Host "`n[PHASE 5] Starting Isolated MariaDB 10.4.32 Daemon..."

    $argList = @(
        "--defaults-file=$drillIniFile",
        "--standalone",
        "--datadir=$drillDataDir",
        "--port=$DrillPort"
    )

    Write-Host "  Launch Command: $mysqldExe $($argList -join ' ')"

    $procInfo = New-Object System.Diagnostics.ProcessStartInfo
    $procInfo.FileName = $mysqldExe
    $procInfo.Arguments = ($argList -join " ")
    $procInfo.UseShellExecute = $false
    $procInfo.CreateNoWindow = $true

    $process = [System.Diagnostics.Process]::Start($procInfo)
    if (-not $process -or $process.HasExited) {
        throw "PROCESS ERROR: Failed to launch mysqld.exe process."
    }

    $childPid = $process.Id
    $childStartTime = $process.StartTime.ToUniversalTime()
    Write-Host "  [OK] Process launched. Child PID: $childPid, StartTime (UTC): $($childStartTime.ToString('o'))"

    # Persist provisional session manifest immediately to prevent untracked orphan daemon
    $metaPath = Join-Path $drillWorkspaceDir "drill_session.json"
    $metaObj = [ordered]@{
        "status"                = "PROVISIONAL"
        "child_pid"             = $childPid
        "start_time_utc"        = $childStartTime.ToString("o")
        "drill_port"            = $DrillPort
        "drill_workspace"       = $drillWorkspaceDir
        "drill_datadir"         = $drillDataDir
        "drill_ini"             = $drillIniFile
        "clean_template_source" = $prodTemplateDir
        "created_at_utc"        = (Get-Date).ToUniversalTime().ToString("o")
        "approved_by"           = $ticket.operator_name
        "ticket_id"             = $ticket.ticket_id
        "verification_stage"    = "PROCESS_LAUNCHED"
    }
    $metaObj | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $metaPath -Encoding UTF8
    Write-Host "  [OK] Provisional session record persisted to '$metaPath'."

    try {
        # Pre-connection Process, Lineage, Executable & Command-Line Audit
        Write-Host "  Auditing process command line, executable path, start time & lineage via CIM..."
        Start-Sleep -Milliseconds 500
        $cimProc = Get-CimInstance Win32_Process -Filter "ProcessId = $childPid" -ErrorAction SilentlyContinue
        if (-not $cimProc) {
            throw "PROCESS AUDIT ERROR: Unable to query process identity for PID $childPid via CIM."
        }

        # 1. Exact Parent PID Lineage Verification
        if ($cimProc.ParentProcessId -ne $PID) {
            Write-Error "PROCESS AUDIT ERROR: Child process PID $childPid ParentProcessId ($($cimProc.ParentProcessId)) does not match runner PID ($PID)."
            throw "SECURITY HALT: Untrusted process lineage. Parent PID $($cimProc.ParentProcessId) does not match runner PID $PID."
        }

        # 2. Exact Process Start Time Verification against Launched Process
        $cimStartTimeUtc = ([DateTime]$cimProc.CreationDate).ToUniversalTime()
        $startDiffSeconds = [math]::Abs(($cimStartTimeUtc - $childStartTime).TotalSeconds)
        if ($startDiffSeconds -gt 2.0) {
            throw "SECURITY HALT: PID $childPid start time ($($cimStartTimeUtc.ToString('o'))) does not match launched process start time ($($childStartTime.ToString('o'))). Discrepancy: $([math]::Round($startDiffSeconds, 2))s."
        }

        # 3. Exact Executable Path Verification
        $canonicalMysqld  = Resolve-SafeCanonicalPath -Path $mysqldExe -MustExist $true
        $procExeCanonical = Resolve-SafeCanonicalPath -Path $cimProc.ExecutablePath -MustExist $true
        if (-not $procExeCanonical.Equals($canonicalMysqld, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "SECURITY HALT: Target process executable '$procExeCanonical' does not match verified mysqld.exe '$canonicalMysqld'."
        }

        # 4. Mandatory Exact Command-Line Verification (Unconditional, Non-Bypassable)
        $cmdLine = $cimProc.CommandLine
        if ([string]::IsNullOrWhiteSpace($cmdLine)) {
            throw "SECURITY HALT: Process command line is empty or inaccessible for PID $childPid."
        }

        if ($cmdLine -match "mysql[\\/]data" -or $cmdLine -match "\b3306\b") {
            throw "SECURITY HALT: Process command line references production datadir or port 3306: $cmdLine"
        }

        $exactDefaultsPattern = '(?i)(?:^|\s)--defaults-file=(?:"?)' + [regex]::Escape($drillIniFile) + '(?:"?)(?:\s|$)'
        if ($cmdLine -notmatch $exactDefaultsPattern) {
            throw "SECURITY HALT: Process command line does not contain mandatory exact --defaults-file argument for '$drillIniFile'. Command line: $cmdLine"
        }

        $metaObj.verification_stage = "PROCESS_AUDITED"
        $metaObj | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $metaPath -Encoding UTF8
        Write-Host "  [OK] Process identity, executable path, start time, and exact --defaults-file confirmed strictly isolated."

        # ----------------------------------------------------------------------
        # STEP 7: SOCKET OWNERSHIP & IDENTITY HANDSHAKE
        # ----------------------------------------------------------------------
        Write-Host "`n[PHASE 6] Awaiting Port $DrillPort Listener & Verifying Socket Ownership..."

        $timeoutSeconds = 20
        $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
        $socketVerified = $false

        while ($stopwatch.Elapsed.TotalSeconds -lt $timeoutSeconds) {
            if ($process.HasExited) {
                $errLogPath = Join-Path $drillLogDir "mariadb_drill_err.log"
                $errSnippet = ""
                if (Test-Path $errLogPath) {
                    $errSnippet = Get-Content -LiteralPath $errLogPath -Tail 20 -Raw
                }
                throw "PROCESS ERROR: MariaDB child process $childPid exited prematurely with code $($process.ExitCode). Error log excerpt:`n$errSnippet"
            }

            $listeners = Get-NetTCPConnection -LocalPort $DrillPort -State Listen -ErrorAction SilentlyContinue
            if ($listeners) {
                $owningPids = $listeners | Select-Object -ExpandProperty OwningProcess -Unique
                if ($owningPids.Count -eq 1 -and $owningPids[0] -eq $childPid) {
                    $socketVerified = $true
                    Write-Host "  [OK] Port $DrillPort is actively listening and owned exclusively by Child PID $childPid."
                    break
                } else {
                    Write-Error "CRITICAL SECURITY BREACH: Port $DrillPort is owned by an unexpected process (PID: $($owningPids -join ', ')), not child PID $childPid."
                    throw "SECURITY ABORT: Socket ownership mismatch."
                }
            }
            Start-Sleep -Milliseconds 500
        }

        if (-not $socketVerified) {
            throw "TIMEOUT ERROR: MariaDB drill daemon failed to bind port $DrillPort within $timeoutSeconds seconds."
        }

        $metaObj.verification_stage = "SOCKET_VERIFIED"
        $metaObj | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $metaPath -Encoding UTF8

        # ----------------------------------------------------------------------
        # STEP 8: IN-BAND BOUNDARY VERIFICATION QUERY
        # ----------------------------------------------------------------------
        Write-Host "`n[PHASE 7] In-Band Boundary Verification Query..."

        $clientArgs = @(
            "--defaults-file=$drillIniFile",
            "--user=root",
            "--port=$DrillPort",
            "--host=127.0.0.1",
            "--batch",
            "--skip-column-names",
            "--execute=SELECT @@port, @@datadir, @@innodb_data_home_dir, @@innodb_log_group_home_dir, @@tmpdir, @@secure_file_priv; SHOW DATABASES;"
        )

        $queryOutput = & $mysqlExe $clientArgs 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "IN-BAND QUERY ERROR: Failed to execute boundary verification query. Output: $queryOutput"
        }

        $lines = $queryOutput -split "`r?`n" | Where-Object { $_.Trim() -ne "" }
        $varLine = $lines[0]
        $varParts = $varLine -split "`t"

        if ($varParts.Count -lt 6) {
            throw "IN-BAND QUERY ERROR: Server returned unexpected variable count ($($varParts.Count) < 6). Output: $varLine"
        }

        $dbPort             = $varParts[0]
        $dbDatadir          = $varParts[1]
        $dbDataHomeDir      = $varParts[2]
        $dbLogGroupHomeDir  = $varParts[3]
        $dbTmpdir           = $varParts[4]
        $dbSecureFilePriv   = $varParts[5]

        Write-Host "  Server Response Variables:"
        Write-Host "    - @@port:                      $dbPort"
        Write-Host "    - @@datadir:                   $dbDatadir"
        Write-Host "    - @@innodb_data_home_dir:      $dbDataHomeDir"
        Write-Host "    - @@innodb_log_group_home_dir: $dbLogGroupHomeDir"
        Write-Host "    - @@tmpdir:                    $dbTmpdir"
        Write-Host "    - @@secure_file_priv:          $dbSecureFilePriv"

        if ($dbPort -ne "$DrillPort") {
            throw "BOUNDARY MISMATCH: Server reported @@port = $dbPort, expected $DrillPort. Disconnecting."
        }

        $normDbDatadir    = Resolve-SafeCanonicalPath -Path $dbDatadir -MustExist $true
        $normExpectedData = Resolve-SafeCanonicalPath -Path $drillDataDir -MustExist $true
        if (-not $normDbDatadir.Equals($normExpectedData, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "BOUNDARY MISMATCH: Server reported @@datadir '$normDbDatadir', expected '$normExpectedData'. Disconnecting."
        }

        $normDbDataHome = Resolve-SafeCanonicalPath -Path $dbDataHomeDir -MustExist $true
        if (-not $normDbDataHome.Equals($normExpectedData, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "BOUNDARY MISMATCH: Server reported @@innodb_data_home_dir '$normDbDataHome', expected '$normExpectedData'. Disconnecting."
        }

        $normDbLogGroupHome = Resolve-SafeCanonicalPath -Path $dbLogGroupHomeDir -MustExist $true
        if (-not $normDbLogGroupHome.Equals($normExpectedData, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "BOUNDARY MISMATCH: Server reported @@innodb_log_group_home_dir '$normDbLogGroupHome', expected '$normExpectedData'. Disconnecting."
        }

        $normDbTmpdir = Resolve-SafeCanonicalPath -Path $dbTmpdir -MustExist $true
        $normExpectedTmp = Resolve-SafeCanonicalPath -Path $drillTmpDir -MustExist $true
        if (-not $normDbTmpdir.Equals($normExpectedTmp, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "BOUNDARY MISMATCH: Server reported @@tmpdir '$normDbTmpdir', expected '$normExpectedTmp'. Disconnecting."
        }

        $normDbSecurePriv = Resolve-SafeCanonicalPath -Path $dbSecureFilePriv -MustExist $true
        if (-not $normDbSecurePriv.Equals($normExpectedTmp, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "BOUNDARY MISMATCH: Server reported @@secure_file_priv '$normDbSecurePriv', expected '$normExpectedTmp'. Disconnecting."
        }

        $dbList = $lines[1..($lines.Count - 1)]
        if ($dbList -contains "rechax_official") {
            throw "CRITICAL ISOLATION FAILURE: 'rechax_official' was returned in SHOW DATABASES! Immediate shutdown triggered."
        }
        Write-Host "  [OK] All server variables match isolated drill environment. 'rechax_official' is absent."

        # Mark manifest fully verified and RUNNING
        $metaObj.status = "RUNNING"
        $metaObj.verification_stage = "FULLY_VERIFIED"
        $metaObj["verified_at_utc"] = (Get-Date).ToUniversalTime().ToString("o")
        $metaObj | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $metaPath -Encoding UTF8
        Write-Host "  [OK] Session metadata recorded in '$metaPath'."

    } catch {
        $failureMessage = $_.ToString()
        $metaObj.status = "VERIFICATION_FAILED_MANUAL_INTERVENTION_REQUIRED"
        $metaObj["last_error"] = $failureMessage
        $metaObj["failed_at_utc"] = (Get-Date).ToUniversalTime().ToString("o")
        try {
            $metaObj | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $metaPath -Encoding UTF8
        } catch {
            Write-Warning "Failed to update session manifest on error: $_"
        }

        Write-Error "POST-LAUNCH ISOLATION CHECK FAILED: $failureMessage"
        Write-Warning "Child process PID $childPid (StartTime UTC: $($childStartTime.ToString('o'))) remains active."
        Write-Warning "Automatic forced termination is strictly prohibited. Durable session record updated at '$metaPath' with state 'VERIFICATION_FAILED_MANUAL_INTERVENTION_REQUIRED' for forensic inspection and authorized manual review."
        throw
    }

    Write-Host "`n======================================================================"
    Write-Host "SUCCESS: Isolated MariaDB Drill Daemon is running and verified."
    Write-Host "Target Port:      $DrillPort"
    Write-Host "Data Directory:   $drillDataDir"
    Write-Host "Child PID:        $childPid"
    Write-Host "Session Manifest: $metaPath"
    Write-Host "======================================================================"
}

# ==============================================================================
# LIFECYCLE BRANCH 2: ISOLATED DRILL DAEMON STATUS AUDIT (READ-ONLY)
# ==============================================================================
if ($Action -eq "Status") {
    Write-Host "`n[ACTION: STATUS] Auditing Isolated MariaDB Drill Daemon Status..."

    if ($DrillPort -eq 3306) {
        Write-Error "SECURITY REFUSAL: Target port cannot be 3306 (Production port). Refusing Status check."
        exit 2
    }

    $sessionInfo = Resolve-ActiveDrillSession -ExplicitManifestPath $SessionManifestPath -Port $DrillPort -BaseDir $DrillBaseDir -ActionName "Status"

    if ($null -eq $sessionInfo) {
        Write-Host "No active drill session manifest found for port $DrillPort in '$DrillBaseDir'."
        $strayListener = Get-NetTCPConnection -LocalPort $DrillPort -State Listen -ErrorAction SilentlyContinue
        if ($strayListener) {
            Write-Error "SECURITY WARNING: Port $DrillPort is listening (PID: $($strayListener[0].OwningProcess)), but no verified drill_session.json matches. Refusing to report unverified process as drill daemon."
            exit 2
        } else {
            Write-Host "Port $DrillPort is not listening. No drill daemon active."
            exit 0
        }
    }

    Write-Host "  Discovered session manifest: '$($sessionInfo.ManifestPath)'"
    $expectedPid      = $sessionInfo.ExpectedPid
    $expectedStartUtc = $sessionInfo.ExpectedStartUtc

    $proc = Get-Process -Id $expectedPid -ErrorAction SilentlyContinue
    if (-not $proc) {
        Write-Warning "Drill process with recorded PID $expectedPid is no longer running in OS."
        Write-Host "Recorded Session Status: $($sessionInfo.Session.status)"
        exit 0
    }

    $procStartTimeUtc = $proc.StartTime.ToUniversalTime()
    $startDiffSeconds = [math]::Abs(($procStartTimeUtc - $expectedStartUtc).TotalSeconds)
    if ($startDiffSeconds -gt 2.0) {
        Write-Error "SECURITY VIOLATION: PID $expectedPid StartTime ($($procStartTimeUtc.ToString('o'))) does not match recorded session start time ($($expectedStartUtc.ToString('o'))). Discrepancy: $([math]::Round($startDiffSeconds, 2))s. PID recycling detected!"
        exit 2
    }

    $cimProc = Get-CimInstance Win32_Process -Filter "ProcessId = $expectedPid" -ErrorAction SilentlyContinue
    if (-not $cimProc) {
        Write-Error "SECURITY VIOLATION: Unable to query Win32_Process identity for PID $expectedPid via CIM."
        exit 2
    }

    $canonicalMysqld  = Resolve-SafeCanonicalPath -Path $mysqldExe -MustExist $true
    $procExeCanonical = Resolve-SafeCanonicalPath -Path $cimProc.ExecutablePath -MustExist $true
    if (-not $procExeCanonical.Equals($canonicalMysqld, [System.StringComparison]::OrdinalIgnoreCase)) {
        Write-Error "SECURITY VIOLATION: Process executable '$procExeCanonical' does not match verified mysqld.exe '$canonicalMysqld'."
        exit 2
    }

    $cmdLine = $cimProc.CommandLine
    if ($cmdLine -match "mysql[\\/]data" -or $cmdLine -match "\b3306\b") {
        Write-Error "SECURITY REFUSAL: Target PID $expectedPid command-line indicates production MariaDB: $cmdLine"
        exit 2
    }
    if ($cmdLine -notmatch [regex]::Escape($sessionInfo.ExpectedIni)) {
        Write-Error "SECURITY REFUSAL: Target PID $expectedPid command-line does not reference recorded drill_my.ini '$($sessionInfo.ExpectedIni)'."
        exit 2
    }

    $listeners = Get-NetTCPConnection -LocalPort $DrillPort -State Listen -ErrorAction SilentlyContinue
    $socketStatus = "NOT_LISTENING"
    if ($listeners) {
        $owningPids = $listeners | Select-Object -ExpandProperty OwningProcess -Unique
        if ($owningPids.Count -eq 1 -and $owningPids[0] -eq $expectedPid) {
            $socketStatus = "LISTENING_VERIFIED (Owned exclusively by PID $expectedPid)"
        } else {
            Write-Error "SECURITY BREACH: Port $DrillPort is owned by an unexpected process (PID: $($owningPids -join ', ')), not recorded drill PID $expectedPid."
            exit 2
        }
    }

    Write-Host "`n======================================================================"
    Write-Host "STATUS REPORT: Isolated MariaDB Drill Daemon"
    Write-Host "Session Status:     $($sessionInfo.Session.status)"
    Write-Host "Verification Stage: $($sessionInfo.Session.verification_stage)"
    Write-Host "Child PID:          $expectedPid"
    Write-Host "Process StartTime:  $($procStartTimeUtc.ToString('o'))"
    Write-Host "Target Port:        $DrillPort"
    Write-Host "Socket State:       $socketStatus"
    Write-Host "Workspace:          $($sessionInfo.ExpectedWorkspace)"
    Write-Host "Data Directory:     $($sessionInfo.Session.drill_datadir)"
    Write-Host "Config File:        $($sessionInfo.ExpectedIni)"
    Write-Host "Ticket ID:          $($sessionInfo.Session.ticket_id)"
    Write-Host "Approved By:        $($sessionInfo.Session.approved_by)"
    Write-Host "======================================================================"
    exit 0
}

# ==============================================================================
# LIFECYCLE BRANCH 3: ISOLATED DRILL DAEMON IN-BAND VERIFICATION (READ-ONLY)
# ==============================================================================
if ($Action -eq "Verify") {
    Write-Host "`n[ACTION: VERIFY] Performing Comprehensive In-Band Verification of Isolated Drill Daemon..."

    if ($DrillPort -eq 3306) {
        Write-Error "SECURITY REFUSAL: Target port cannot be 3306 (Production port). Refusing Verify check."
        exit 2
    }

    $sessionInfo = Resolve-ActiveDrillSession -ExplicitManifestPath $SessionManifestPath -Port $DrillPort -BaseDir $DrillBaseDir -ActionName "Verify"
    if ($null -eq $sessionInfo) {
        Write-Error "VERIFICATION ERROR: No active drill session manifest found for port $DrillPort in '$DrillBaseDir'. Refusing verification."
        exit 2
    }

    $expectedPid       = $sessionInfo.ExpectedPid
    $expectedStartUtc  = $sessionInfo.ExpectedStartUtc
    $expectedWorkspace = $sessionInfo.ExpectedWorkspace
    $expectedIni       = $sessionInfo.ExpectedIni

    # 1. Process existence
    $proc = Get-Process -Id $expectedPid -ErrorAction SilentlyContinue
    if (-not $proc) {
        throw "VERIFICATION FAILURE: Recorded drill process PID $expectedPid is not running in OS."
    }

    # 2. Anti-PID-reuse start time check
    $procStartTimeUtc = $proc.StartTime.ToUniversalTime()
    $startDiffSeconds = [math]::Abs(($procStartTimeUtc - $expectedStartUtc).TotalSeconds)
    if ($startDiffSeconds -gt 2.0) {
        throw "SECURITY VIOLATION: PID $expectedPid StartTime ($($procStartTimeUtc.ToString('o'))) does not match recorded session start time ($($expectedStartUtc.ToString('o'))). PID recycling detected!"
    }

    # 3. Executable & Command Line CIM Audit
    $cimProc = Get-CimInstance Win32_Process -Filter "ProcessId = $expectedPid" -ErrorAction SilentlyContinue
    if (-not $cimProc) {
        throw "SECURITY VIOLATION: Unable to query Win32_Process identity for PID $expectedPid via CIM."
    }

    $canonicalMysqld  = Resolve-SafeCanonicalPath -Path $mysqldExe -MustExist $true
    $procExeCanonical = Resolve-SafeCanonicalPath -Path $cimProc.ExecutablePath -MustExist $true
    if (-not $procExeCanonical.Equals($canonicalMysqld, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "SECURITY VIOLATION: Target process executable '$procExeCanonical' does not match verified mysqld.exe '$canonicalMysqld'."
    }

    $cmdLine = $cimProc.CommandLine
    if ($cmdLine -match "mysql[\\/]data" -or $cmdLine -match "\b3306\b") {
        throw "SECURITY REFUSAL: Target PID $expectedPid command-line indicates production MariaDB: $cmdLine"
    }
    if ($cmdLine -notmatch [regex]::Escape($expectedIni)) {
        throw "SECURITY REFUSAL: Target PID $expectedPid command-line does not reference recorded drill_my.ini '$expectedIni'."
    }

    # 4. Socket Listener Ownership
    $listeners = Get-NetTCPConnection -LocalPort $DrillPort -State Listen -ErrorAction SilentlyContinue
    if (-not $listeners) {
        throw "VERIFICATION FAILURE: MariaDB drill daemon is alive but no listener is active on port $DrillPort."
    }
    $owningPid = $listeners[0].OwningProcess
    if ($owningPid -ne $expectedPid) {
        throw "CRITICAL SECURITY REFUSAL: Port $DrillPort listener is owned by PID $owningPid, not recorded drill PID $expectedPid! Refusing verification."
    }

    # 5. In-Band Query Verification via mysql.exe
    Write-Host "  Executing in-band boundary verification query on port $DrillPort..."
    $clientArgs = @(
        "--defaults-file=$expectedIni",
        "--user=root",
        "--port=$DrillPort",
        "--host=127.0.0.1",
        "--batch",
        "--skip-column-names",
        "--execute=SELECT @@port, @@datadir, @@innodb_data_home_dir, @@innodb_log_group_home_dir, @@tmpdir, @@secure_file_priv; SHOW DATABASES;"
    )

    $queryOutput = & $mysqlExe $clientArgs 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "IN-BAND QUERY ERROR: Failed to execute boundary verification query. Output: $queryOutput"
    }

    $lines = $queryOutput -split "`r?`n" | Where-Object { $_.Trim() -ne "" }
    $varLine = $lines[0]
    $varParts = $varLine -split "`t"

    if ($varParts.Count -lt 6) {
        throw "IN-BAND QUERY ERROR: Server returned unexpected variable count ($($varParts.Count) < 6). Output: $varLine"
    }

    $dbPort             = $varParts[0]
    $dbDatadir          = $varParts[1]
    $dbDataHomeDir      = $varParts[2]
    $dbLogGroupHomeDir  = $varParts[3]
    $dbTmpdir           = $varParts[4]
    $dbSecureFilePriv   = $varParts[5]

    Write-Host "  Server Response Variables:"
    Write-Host "    - @@port:                      $dbPort"
    Write-Host "    - @@datadir:                   $dbDatadir"
    Write-Host "    - @@innodb_data_home_dir:      $dbDataHomeDir"
    Write-Host "    - @@innodb_log_group_home_dir: $dbLogGroupHomeDir"
    Write-Host "    - @@tmpdir:                    $dbTmpdir"
    Write-Host "    - @@secure_file_priv:          $dbSecureFilePriv"

    if ($dbPort -ne "$DrillPort") {
        throw "BOUNDARY MISMATCH: Server reported @@port = $dbPort, expected $DrillPort. Disconnecting."
    }

    $drillDataDir = Join-Path $expectedWorkspace "data"
    $drillTmpDir  = Join-Path $expectedWorkspace "tmp"

    $normDbDatadir    = Resolve-SafeCanonicalPath -Path $dbDatadir -MustExist $true
    $normExpectedData = Resolve-SafeCanonicalPath -Path $drillDataDir -MustExist $true
    if (-not $normDbDatadir.Equals($normExpectedData, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "BOUNDARY MISMATCH: Server reported @@datadir '$normDbDatadir', expected '$normExpectedData'."
    }

    $normDbDataHome = Resolve-SafeCanonicalPath -Path $dbDataHomeDir -MustExist $true
    if (-not $normDbDataHome.Equals($normExpectedData, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "BOUNDARY MISMATCH: Server reported @@innodb_data_home_dir '$normDbDataHome', expected '$normExpectedData'."
    }

    $normDbLogGroupHome = Resolve-SafeCanonicalPath -Path $dbLogGroupHomeDir -MustExist $true
    if (-not $normDbLogGroupHome.Equals($normExpectedData, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "BOUNDARY MISMATCH: Server reported @@innodb_log_group_home_dir '$normDbLogGroupHome', expected '$normExpectedData'."
    }

    $normDbTmpdir = Resolve-SafeCanonicalPath -Path $dbTmpdir -MustExist $true
    $normExpectedTmp = Resolve-SafeCanonicalPath -Path $drillTmpDir -MustExist $true
    if (-not $normDbTmpdir.Equals($normExpectedTmp, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "BOUNDARY MISMATCH: Server reported @@tmpdir '$normDbTmpdir', expected '$normExpectedTmp'."
    }

    $normDbSecurePriv = Resolve-SafeCanonicalPath -Path $dbSecureFilePriv -MustExist $true
    if (-not $normDbSecurePriv.Equals($normExpectedTmp, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "BOUNDARY MISMATCH: Server reported @@secure_file_priv '$normDbSecurePriv', expected '$normExpectedTmp'."
    }

    $dbList = $lines[1..($lines.Count - 1)]
    if ($dbList -contains "rechax_official") {
        throw "CRITICAL ISOLATION FAILURE: 'rechax_official' was returned in SHOW DATABASES! Immediate abort."
    }

    Write-Host "`n======================================================================"
    Write-Host "VERIFICATION SUCCESS: Isolated MariaDB Drill Daemon strictly isolated."
    Write-Host "Target Port:      $DrillPort"
    Write-Host "Child PID:        $expectedPid"
    Write-Host "Data Directory:   $drillDataDir"
    Write-Host "Databases Found:  $($dbList -join ', ')"
    Write-Host "Status:           ISOLATED AND VERIFIED (rechax_official absent)"
    Write-Host "======================================================================"
    exit 0
}

# ==============================================================================
# LIFECYCLE BRANCH 4: VERIFIED GRACEFUL SHUTDOWN HANDLER
# ==============================================================================
if ($Action -eq "Shutdown") {
    Write-Host "`n[ACTION: SHUTDOWN] Performing Authenticated Shutdown of Isolated Drill Daemon..."

    # --------------------------------------------------------------------------
    # STEP 1: SHUTDOWN CONFIRMATION GUARD
    # --------------------------------------------------------------------------
    if (-not $ConfirmShutdown) {
        Write-Warning "CRITICAL EXECUTION REFUSAL: Shutdown confirmation guard is active."
        Write-Warning "To shut down an isolated drill daemon, explicit operator confirmation is required."
        Write-Warning "Required parameters: -Action Shutdown -ConfirmShutdown [-SessionManifestPath '<path_to_drill_session.json>']"
        Write-Warning "No processes terminated, no database state modified."
        Write-Host "Result: REFUSED (HOLD)" -ForegroundColor Yellow
        exit 2
    }

    # --------------------------------------------------------------------------
    # STEP 2: SESSION MANIFEST DISCOVERY & VALIDATION
    # --------------------------------------------------------------------------
    $sessionInfo = Resolve-ActiveDrillSession -ExplicitManifestPath $SessionManifestPath -Port $DrillPort -BaseDir $DrillBaseDir -ActionName "Shutdown"

    if ($null -eq $sessionInfo) {
        Write-Host "No active drill session manifest found for port $DrillPort in '$DrillBaseDir'."
        Write-Host "Checking if any process is listening on port $DrillPort..."
        $strayListener = Get-NetTCPConnection -LocalPort $DrillPort -State Listen -ErrorAction SilentlyContinue
        if ($strayListener) {
            Write-Error "SECURITY REFUSAL: Port $DrillPort is listening (PID: $($strayListener[0].OwningProcess)), but no verified drill_session.json matches. Refusing to terminate unverified process."
            exit 2
        } else {
            Write-Host "Port $DrillPort is not listening. Nothing to shut down."
            exit 0
        }
    }

    $resolvedManifestPath = $sessionInfo.ManifestPath
    $session              = $sessionInfo.Session
    $expectedPid          = $sessionInfo.ExpectedPid
    $expectedStartUtc     = $sessionInfo.ExpectedStartUtc
    $expectedWorkspace    = $sessionInfo.ExpectedWorkspace
    $expectedIni          = $sessionInfo.ExpectedIni

    Write-Host "  Using authenticated session manifest: '$resolvedManifestPath'"

    if ($session.status -notin @("RUNNING", "PROVISIONAL", "VERIFICATION_FAILED_MANUAL_INTERVENTION_REQUIRED")) {
        Write-Host "Session status is '$($session.status)' (not eligible for shutdown). Nothing to shut down."
        exit 0
    }

    # --------------------------------------------------------------------------
    # STEP 3: STRICT TARGET PROCESS IDENTITY & LINEAGE AUTHENTICATION
    # --------------------------------------------------------------------------
    Write-Host "  Authenticating target process identity (PID: $expectedPid)..."

    # Check 1: Process existence
    $proc = Get-Process -Id $expectedPid -ErrorAction SilentlyContinue
    if (-not $proc) {
        Write-Warning "Drill process with recorded PID $expectedPid is no longer running in OS."
        # Update manifest to DEAD
        $session.status = "DEAD"
        $session.stopped_at_utc = (Get-Date).ToUniversalTime().ToString("o")
        $session | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $resolvedManifestPath -Encoding UTF8
        Write-Host "[OK] Session manifest marked as DEAD."
        exit 0
    }

    # Check 2: Process start time match (Anti-PID-Reuse Check)
    $procStartTimeUtc = $proc.StartTime.ToUniversalTime()
    $startDiffSeconds = [math]::Abs(($procStartTimeUtc - $expectedStartUtc).TotalSeconds)
    if ($startDiffSeconds -gt 2.0) {
        throw "SECURITY VIOLATION: PID $expectedPid StartTime ($($procStartTimeUtc.ToString('o'))) does not match recorded session start time ($($expectedStartUtc.ToString('o'))). PID recycling detected! Refusing shutdown."
    }
    Write-Host "  [OK] Process StartTime matches recorded session (Diff: $([math]::Round($startDiffSeconds, 2))s)."

    # Check 3: Process executable path & command-line audit via CIM
    $cimProc = Get-CimInstance Win32_Process -Filter "ProcessId = $expectedPid" -ErrorAction SilentlyContinue
    if (-not $cimProc) {
        throw "SECURITY VIOLATION: Unable to query Win32_Process identity for PID $expectedPid via CIM."
    }

    $canonicalMysqld = Resolve-SafeCanonicalPath -Path $mysqldExe -MustExist $true
    $procExeCanonical = Resolve-SafeCanonicalPath -Path $cimProc.ExecutablePath -MustExist $true
    if (-not $procExeCanonical.Equals($canonicalMysqld, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "SECURITY VIOLATION: Target process executable '$procExeCanonical' does not match verified mysqld.exe '$canonicalMysqld'. Refusing shutdown."
    }

    $cmdLine = $cimProc.CommandLine
    if ($cmdLine -match "mysql[\\/]data" -or $cmdLine -match "3306") {
        throw "SECURITY REFUSAL: Target PID $expectedPid command-line indicates production MariaDB! Refusing shutdown."
    }
    if ($cmdLine -notmatch [regex]::Escape($expectedIni)) {
        throw "SECURITY REFUSAL: Target PID $expectedPid command-line does not reference the recorded drill_my.ini '$expectedIni'! Refusing shutdown."
    }
    Write-Host "  [OK] Process executable and command line strictly match recorded isolated drill instance."

    # Check 4: Socket ownership verification
    $listeners = Get-NetTCPConnection -LocalPort $DrillPort -State Listen -ErrorAction SilentlyContinue
    if (-not $listeners) {
        Write-Warning "Process $expectedPid is alive but no listener is active on port $DrillPort."
    } else {
        $owningPid = $listeners[0].OwningProcess
        if ($owningPid -ne $expectedPid) {
            throw "CRITICAL SECURITY REFUSAL: Port $DrillPort listener is owned by PID $owningPid, not recorded drill PID $expectedPid! Refusing shutdown."
        }
        Write-Host "  [OK] Port $DrillPort listening socket confirmed owned by PID $expectedPid."
    }

    # --------------------------------------------------------------------------
    # STEP 4: VERIFIED SHUTDOWN EXECUTION (GRACEFUL ONLY - NO HARD KILL)
    # --------------------------------------------------------------------------
    if (-not (Test-Path -LiteralPath $mysqladminExe)) {
        throw "PREFLIGHT ERROR: Required mysqladmin binary not found: '$mysqladminExe'"
    }

    Write-Host "  Issuing graceful shutdown via mysqladmin on dedicated port $DrillPort..."
    $adminArgs = @(
        "--defaults-file=$expectedIni",
        "--user=root",
        "--port=$DrillPort",
        "--host=127.0.0.1",
        "shutdown"
    )

    # Capture stdout, stderr, and exit code from mysqladmin invocation
    $adminOutput = & $mysqladminExe $adminArgs 2>&1
    $adminExitCode = $LASTEXITCODE

    Write-Host "  mysqladmin exit code: $adminExitCode"
    if ($adminOutput) {
        Write-Host "  mysqladmin output: $($adminOutput -join '; ')"
    }

    if ($adminExitCode -ne 0) {
        Write-Warning "SHUTDOWN WARNING: mysqladmin returned non-zero exit code: $adminExitCode."
        # Update manifest to indicate shutdown command failure
        $session.status = "SHUTDOWN_COMMAND_FAILED_MANUAL_INTERVENTION_REQUIRED"
        $session.last_error = "mysqladmin returned exit code $adminExitCode : $($adminOutput -join ' ')"
        $session.stopped_at_utc = (Get-Date).ToUniversalTime().ToString("o")
        $session | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $resolvedManifestPath -Encoding UTF8

        Write-Error "SHUTDOWN FAILURE: Graceful shutdown command failed (Exit code: $adminExitCode). Automatic forced termination is strictly prohibited. Process $expectedPid remains running; workspace preserved at '$expectedWorkspace'."
        exit 2
    }

    Write-Host "  Awaiting graceful process exit (timeout: 15s)..."
    $cleanExit = $proc.WaitForExit(15000)

    if ($cleanExit -and $proc.HasExited) {
        Write-Host "  [OK] Drill process $expectedPid has gracefully exited."

        # ----------------------------------------------------------------------
        # STEP 5: POST-SHUTDOWN METADATA UPDATE & WORKSPACE PRESERVATION
        # ----------------------------------------------------------------------
        $session.status = "STOPPED"
        $session.stopped_at_utc = (Get-Date).ToUniversalTime().ToString("o")
        $session | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $resolvedManifestPath -Encoding UTF8

        Write-Host "`n======================================================================"
        Write-Host "SUCCESS: Isolated MariaDB Drill Daemon shut down safely."
        Write-Host "Target Port:      $DrillPort"
        Write-Host "Stopped PID:      $expectedPid"
        Write-Host "Session Manifest: $resolvedManifestPath (Status: STOPPED)"
        Write-Host "Workspace State:  Preserved in place for forensic audit (No auto-delete)."
        Write-Host "======================================================================"
    } else {
        # Shutdown timeout: NEVER force-kill. Fail closed and require operator intervention.
        Write-Warning "SHUTDOWN TIMEOUT: MariaDB drill process $expectedPid did not exit within 15 seconds."
        Write-Warning "Automatic forced termination (Stop-Process, Kill, TerminateProcess) is strictly prohibited to prevent data corruption and PID races."

        $session.status = "SHUTDOWN_TIMEOUT_MANUAL_INTERVENTION_REQUIRED"
        $session.last_error = "Process $expectedPid did not terminate within 15000ms after mysqladmin shutdown command."
        $session.attention_required_at_utc = (Get-Date).ToUniversalTime().ToString("o")
        $session | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $resolvedManifestPath -Encoding UTF8

        Write-Error "SHUTDOWN TIMEOUT: MariaDB drill process $expectedPid did not exit within 15s. Process remains running for forensic inspection. Workspace preserved at '$expectedWorkspace'."
        exit 2
    }
}

# Final status
Write-Host "`nExecution completed. Production migration and restore remain on HOLD."
