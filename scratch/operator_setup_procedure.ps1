# scratch/operator_setup_procedure.ps1
# ==============================================================================
# TopUpers Gate 2B -- Elevated Operator Administrative Infrastructure Provisioner
# ==============================================================================
# PURPOSE:
# Provisions protected administrative roots and operator approval ticket required
# for TopUpers Gate 2B isolated drill daemon provisioning.
#
# REQUIREMENT:
# Must be executed in an elevated PowerShell session ("Run as Administrator")
# by an authorized system operator.
# ==============================================================================

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [string]$OperatorName = "DESKTOP-19285V4\TalhaX",

    [Parameter(Mandatory = $false)]
    [string]$RunnerSid = "S-1-5-21-576183998-3037833486-4072321642-1002"
)

$ErrorActionPreference = "Stop"

Write-Host "======================================================================"
Write-Host "TopUpers Gate 2B -- Administrative Infrastructure and Ticket Setup"
Write-Host "======================================================================"

# 1. Elevation check
$principal = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
$isAdmin = $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Error "ELEVATION REQUIRED: This script must be run from an elevated PowerShell console (Run as Administrator)."
    exit 1
}
Write-Host "[OK] Elevated Administrator session confirmed."

$SID_SYSTEM         = "S-1-5-18"
$SID_ADMINISTRATORS = "S-1-5-32-544"

$adminSidObj  = [System.Security.Principal.SecurityIdentifier]::new($SID_ADMINISTRATORS)
$systemSidObj = [System.Security.Principal.SecurityIdentifier]::new($SID_SYSTEM)
$runnerSidObj = [System.Security.Principal.SecurityIdentifier]::new($RunnerSid)

$authRoot   = "C:\TopUpers_AdminAuth"
$ticketsDir = "C:\TopUpers_AdminAuth\tickets"
$ledgerDir  = "C:\TopUpers_AdminAuth\ledger"
$stagingDir = "C:\TopUpers_DrillStaging"

$targetDirs = @($authRoot, $ticketsDir, $ledgerDir, $stagingDir)

# 2. Check pre-existing target paths (must be absent or genuine non-reparse directory)
foreach ($dir in $targetDirs) {
    if (Test-Path -LiteralPath $dir) {
        $item = Get-Item -LiteralPath $dir -Force
        if (($item.Attributes -band [System.IO.FileAttributes]::Directory) -eq 0) {
            Write-Error "SECURITY HALT: Existing target '$dir' is a file, not a directory. Aborting."
            exit 2
        }
        if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
            Write-Error "SECURITY HALT: Existing target '$dir' is a reparse point/junction. Aborting."
            exit 2
        }
    }
}

# 3. Create directories cleanly
foreach ($dir in $targetDirs) {
    if (-not (Test-Path -LiteralPath $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created directory: '$dir'"
    } else {
        Write-Host "Directory already exists: '$dir'"
    }
}

# 4. Configure strict ACLs on AuthRoot, Tickets, Ledger (Admins + SYSTEM Only)
$authDirs = @($authRoot, $ticketsDir, $ledgerDir)
foreach ($dir in $authDirs) {
    $acl = New-Object System.Security.AccessControl.DirectorySecurity
    $acl.SetOwner($adminSidObj)
    # Block inheritance and purge inherited rules ($isProtected = $true, $preserveInheritance = $false)
    $acl.SetAccessRuleProtection($true, $false)

    # Allow Administrators FullControl (Inherit to containers and objects)
    $adminRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $adminSidObj,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.InheritanceFlags]"ContainerInherit, ObjectInherit",
        [System.Security.AccessControl.PropagationFlags]::None,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $acl.AddAccessRule($adminRule)

    # Allow SYSTEM FullControl
    $systemRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $systemSidObj,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.InheritanceFlags]"ContainerInherit, ObjectInherit",
        [System.Security.AccessControl.PropagationFlags]::None,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $acl.AddAccessRule($systemRule)

    Set-Acl -LiteralPath $dir -AclObject $acl
    Write-Host "Configured strict administrative ACL on '$dir'"
}

# 5. Configure strict ACL on StagingDir (Admins + SYSTEM + Current Runner FullControl)
$stagingAcl = New-Object System.Security.AccessControl.DirectorySecurity
$stagingAcl.SetOwner($adminSidObj)
$stagingAcl.SetAccessRuleProtection($true, $false)

$adminRuleStaging = New-Object System.Security.AccessControl.FileSystemAccessRule(
    $adminSidObj,
    [System.Security.AccessControl.FileSystemRights]::FullControl,
    [System.Security.AccessControl.InheritanceFlags]"ContainerInherit, ObjectInherit",
    [System.Security.AccessControl.PropagationFlags]::None,
    [System.Security.AccessControl.AccessControlType]::Allow
)
$stagingAcl.AddAccessRule($adminRuleStaging)

$systemRuleStaging = New-Object System.Security.AccessControl.FileSystemAccessRule(
    $systemSidObj,
    [System.Security.AccessControl.FileSystemRights]::FullControl,
    [System.Security.AccessControl.InheritanceFlags]"ContainerInherit, ObjectInherit",
    [System.Security.AccessControl.PropagationFlags]::None,
    [System.Security.AccessControl.AccessControlType]::Allow
)
$stagingAcl.AddAccessRule($systemRuleStaging)

$runnerRuleStaging = New-Object System.Security.AccessControl.FileSystemAccessRule(
    $runnerSidObj,
    [System.Security.AccessControl.FileSystemRights]::FullControl,
    [System.Security.AccessControl.InheritanceFlags]"ContainerInherit, ObjectInherit",
    [System.Security.AccessControl.PropagationFlags]::None,
    [System.Security.AccessControl.AccessControlType]::Allow
)
$stagingAcl.AddAccessRule($runnerRuleStaging)

Set-Acl -LiteralPath $stagingDir -AclObject $stagingAcl
Write-Host "Configured strict ACL on '$stagingDir' (Runner: $RunnerSid)"

# 6. Generate RFC 4122 UUIDv4 Operator Approval Ticket
$ticketId = [Guid]::NewGuid().ToString()
$ticketPath = Join-Path $ticketsDir "$ticketId.json"
$timestampUtc = (Get-Date).ToUniversalTime().ToString("o")

$ticketData = [ordered]@{
    "ticket_id"         = $ticketId
    "operator_name"     = $OperatorName
    "authorized_action" = "PROVISION_ISOLATED_MARIADB_DRILL"
    "target_port"       = 3308
    "environment"       = "RECHAX_DRILL"
    "timestamp_utc"     = $timestampUtc
}

$ticketJson = $ticketData | ConvertTo-Json -Depth 4

try {
    $ticketStream = [System.IO.File]::Open(
        $ticketPath,
        [System.IO.FileMode]::CreateNew,
        [System.IO.FileAccess]::Write,
        [System.IO.FileShare]::None
    )
} catch [System.IO.IOException] {
    Write-Error "TICKET CREATION REFUSAL: Ticket file already exists at '$ticketPath'. Refusing overwrite."
    exit 2
} catch {
    Write-Error "TICKET CREATION ERROR: Failed to create exclusive ticket file at '$ticketPath': $_"
    exit 2
}

try {
    $writer = [System.IO.StreamWriter]::new($ticketStream, [System.Text.Encoding]::UTF8)
    $writer.Write($ticketJson)
    $writer.Flush()
} finally {
    $ticketStream.Close()
    $ticketStream.Dispose()
}

try {
    # Set ticket file ACL
    $ticketFileAcl = New-Object System.Security.AccessControl.FileSecurity
    $ticketFileAcl.SetOwner($adminSidObj)
    $ticketFileAcl.SetAccessRuleProtection($true, $false)

    $adminFileRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $adminSidObj,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $ticketFileAcl.AddAccessRule($adminFileRule)

    $systemFileRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $systemSidObj,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $ticketFileAcl.AddAccessRule($systemFileRule)

    # Runner needs Read access to parse ticket
    $runnerFileRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $runnerSidObj,
        [System.Security.AccessControl.FileSystemRights]::Read,
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $ticketFileAcl.AddAccessRule($runnerFileRule)

    Set-Acl -LiteralPath $ticketPath -AclObject $ticketFileAcl
    Write-Host "Generated operator authorization ticket: '$ticketPath'"

    # 7. Post-Provisioning Verification
    Write-Host ""
    Write-Host "--- On-Disk Security Descriptor Verification ---"
    $allPassed = $true

    foreach ($dir in $authDirs) {
        $discAcl = Get-Acl -LiteralPath $dir
        $owner = $discAcl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
        $prot = $discAcl.AreAccessRulesProtected
        Write-Host "Dir '$dir' -> Owner: $owner, InheritanceProtected: $prot"
        if ($owner -ne $SID_ADMINISTRATORS -or -not $prot) {
            $allPassed = $false
        }
    }

    $discStagingAcl = Get-Acl -LiteralPath $stagingDir
    $stagingOwner = $discStagingAcl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
    $stagingProt = $discStagingAcl.AreAccessRulesProtected
    Write-Host "Dir '$stagingDir' -> Owner: $stagingOwner, InheritanceProtected: $stagingProt"
    if ($stagingOwner -ne $SID_ADMINISTRATORS -or -not $stagingProt) {
        $allPassed = $false
    }

    $discTicketAcl = Get-Acl -LiteralPath $ticketPath
    $ticketOwner = $discTicketAcl.GetOwner([System.Security.Principal.SecurityIdentifier]).Value
    $ticketProt = $discTicketAcl.AreAccessRulesProtected
    Write-Host "Ticket '$ticketPath' -> Owner: $ticketOwner, InheritanceProtected: $ticketProt"
    if ($ticketOwner -ne $SID_ADMINISTRATORS -or -not $ticketProt) {
        $allPassed = $false
    }

    if (-not $allPassed) {
        throw "Security descriptor verification failed for one or more target objects."
    }

    Write-Host ""
    Write-Host "======================================================================"
    Write-Host "SETUP SUCCESSFUL: All administrative infrastructure verified."
    Write-Host "Ticket ID:        $ticketId"
    Write-Host "Ticket Path:      $ticketPath"
    Write-Host "Next Step:        Run provision_isolated_drill_daemon.ps1 -Action Provision"
    Write-Host "======================================================================"
} catch {
    Write-Error "SETUP ERROR: $_"
    if (Test-Path -LiteralPath $ticketPath) {
        try {
            Remove-Item -LiteralPath $ticketPath -Force -ErrorAction Stop
            Write-Warning "CLEANUP: Removed unverified/incomplete ticket file '$ticketPath'."
        } catch {
            Write-Error "CLEANUP FAILURE: Failed to remove unverified ticket file '$ticketPath': $_. Manual deletion required!"
        }
    }
    exit 2
}
