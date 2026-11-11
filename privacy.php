<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Privacy Policy — TopUpers';
$pageDescription = 'Official Privacy Policy for TopUpers gaming top-up store in Bangladesh. Learn how we collect, protect, and handle your data.';

include __DIR__ . '/includes/header.php';

$siteName = !empty(getSetting('site_name')) ? getSetting('site_name') : (defined('APP_NAME') ? APP_NAME : 'TopUpers');
$siteEmail = !empty(getSetting('site_email')) ? getSetting('site_email') : 'support@topupers.com';
?>

<!-- Reading Progress Bar -->
<div id="privacyProgressBar" aria-hidden="true"></div>

<!-- Custom Scoped Styles for Modern Privacy Policy -->
<style>
  /* Reading Progress Bar */
  #privacyProgressBar {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    width: 0%;
    background: linear-gradient(90deg, var(--rx-primary, #16B8FF), #8B5CF6, var(--rx-secondary, #6857FF));
    z-index: 999999;
    transition: width 0.1s ease-out;
  }

  /* Hero Section */
  .privacy-hero-wrap {
    position: relative;
    background: radial-gradient(1000px circle at 50% -20%, rgba(22, 184, 255, 0.12), transparent 70%),
                linear-gradient(180deg, var(--rx-bg-secondary, #0A0E18) 0%, var(--rx-bg, #070A12) 100%);
    border-bottom: 1px solid var(--rx-border, #222A3D);
    padding: 3.5rem 0 3rem;
    overflow: hidden;
  }
  html.light-mode .privacy-hero-wrap {
    background: radial-gradient(1000px circle at 50% -20%, rgba(7, 155, 232, 0.08), transparent 70%),
                linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
    border-bottom-color: var(--rx-border, #E1E5EE);
  }
  .privacy-hero-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    opacity: 0.6;
  }
  html.light-mode .privacy-hero-wrap::before {
    background-image: radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    opacity: 0.8;
  }

  .privacy-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(22, 184, 255, 0.1);
    border: 1px solid rgba(22, 184, 255, 0.25);
    color: var(--rx-primary, #16B8FF);
    font-size: 0.84rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 1.25rem;
  }
  html.light-mode .privacy-badge-pill {
    background: rgba(7, 155, 232, 0.08);
    border-color: rgba(7, 155, 232, 0.2);
    color: #0284c7;
  }

  .privacy-hero-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    letter-spacing: -0.5px;
    color: var(--rx-text, #F5F7FF);
    line-height: 1.15;
    margin-bottom: 1rem;
  }
  .privacy-hero-title span {
    background: linear-gradient(135deg, var(--rx-primary, #16B8FF), #A78BFA);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .privacy-hero-lead {
    font-size: 1.05rem;
    color: var(--rx-text-secondary, #A5AEC2);
    max-width: 740px;
    margin: 0 auto 2rem;
    line-height: 1.6;
  }

  /* Metadata Strip */
  .privacy-meta-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 2rem;
  }
  .privacy-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    font-size: 0.85rem;
    color: var(--rx-text-secondary, #A5AEC2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
  html.light-mode .privacy-meta-item {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    color: #475569;
  }
  .privacy-meta-item strong {
    color: var(--rx-text, #F5F7FF);
    font-weight: 600;
  }
  html.light-mode .privacy-meta-item strong { color: #0F172A; }
  .privacy-meta-item i {
    color: var(--rx-primary, #16B8FF);
    font-size: 0.95rem;
  }
  .status-pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22C55E;
    box-shadow: 0 0 8px #22C55E;
    display: inline-block;
    animation: statusPulse 2s infinite ease-in-out;
  }
  @keyframes statusPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
  }

  /* Action Bar & Search */
  .privacy-hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .privacy-search-box {
    position: relative;
    width: 100%;
    max-width: 380px;
  }
  .privacy-search-box input {
    width: 100%;
    padding: 10px 16px 10px 42px;
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 12px;
    color: var(--rx-text, #F5F7FF);
    font-size: 0.92rem;
    outline: none;
    transition: all 0.25s ease;
  }
  html.light-mode .privacy-search-box input {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #0F172A;
  }
  .privacy-search-box input:focus {
    border-color: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 0 3px rgba(22, 184, 255, 0.15);
  }
  .privacy-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rx-text-muted, #6F7890);
    font-size: 0.95rem;
    pointer-events: none;
  }
  .privacy-btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    border: 1px solid var(--rx-border, #222A3D);
    background: var(--rx-surface, #111625);
    color: var(--rx-text, #F5F7FF);
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  html.light-mode .privacy-btn-action {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #1E293B;
  }
  .privacy-btn-action:hover {
    background: var(--rx-surface-hover, #151C2D);
    border-color: var(--rx-primary, #16B8FF);
    color: var(--rx-primary, #16B8FF);
    transform: translateY(-1px);
  }

  /* Executive Summary Highlight Cards */
  .privacy-highlights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 2.5rem;
  }
  .privacy-highlight-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 14px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  html.light-mode .privacy-highlight-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
  }
  .privacy-highlight-card:hover {
    transform: translateY(-3px);
    border-color: rgba(22, 184, 255, 0.4);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }
  html.light-mode .privacy-highlight-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  }
  .highlight-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 14px;
  }
  .icon-teal { background: rgba(22, 184, 255, 0.12); color: #16B8FF; border: 1px solid rgba(22, 184, 255, 0.25); }
  .icon-green { background: rgba(34, 197, 94, 0.12); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.25); }
  .icon-purple { background: rgba(139, 92, 246, 0.12); color: #A78BFA; border: 1px solid rgba(139, 92, 246, 0.25); }
  .icon-amber { background: rgba(245, 158, 11, 0.12); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.25); }

  .privacy-highlight-card h4 {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .privacy-highlight-card h4 { color: #0F172A; }
  .privacy-highlight-card p {
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.55;
  }

  /* Layout */
  .privacy-container-wrap {
    padding: 3rem 0 5rem;
  }

  /* Sticky Sidebar Navigation */
  .privacy-sidebar {
    position: sticky;
    top: 90px;
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
    max-height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
  }
  html.light-mode .privacy-sidebar {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  }
  .privacy-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--rx-border, #222A3D);
  }
  html.light-mode .privacy-sidebar-header { border-bottom-color: #E2E8F0; }
  .privacy-sidebar-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    font-family: 'Outfit', sans-serif;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  html.light-mode .privacy-sidebar-title { color: #0F172A; }
  .privacy-count-badge {
    background: rgba(22, 184, 255, 0.12);
    color: var(--rx-primary, #16B8FF);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .privacy-toc-list {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    padding-right: 4px;
    flex: 1;
  }
  .privacy-toc-list::-webkit-scrollbar { width: 4px; }
  .privacy-toc-list::-webkit-scrollbar-thumb {
    background: var(--rx-border, #222A3D);
    border-radius: 4px;
  }
  .privacy-toc-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--rx-text-secondary, #A5AEC2);
    text-decoration: none;
    transition: all 0.2s ease;
    margin-bottom: 3px;
    line-height: 1.35;
  }
  html.light-mode .privacy-toc-link { color: #64748B; }
  .privacy-toc-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--rx-primary, #16B8FF);
  }
  html.light-mode .privacy-toc-link:hover {
    background: rgba(0, 0, 0, 0.04);
    color: var(--rx-primary, #079BE8);
  }
  .privacy-toc-link.active {
    background: rgba(22, 184, 255, 0.12);
    color: var(--rx-primary, #16B8FF);
    font-weight: 600;
    border-left: 3px solid var(--rx-primary, #16B8FF);
  }
  html.light-mode .privacy-toc-link.active {
    background: rgba(7, 155, 232, 0.08);
    color: #0284c7;
    border-left-color: #0284c7;
  }
  .toc-index {
    font-family: 'Rajdhani', monospace;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--rx-text-muted, #6F7890);
    width: 22px;
    flex-shrink: 0;
  }
  .privacy-toc-link.active .toc-index {
    color: var(--rx-primary, #16B8FF);
  }

  .sidebar-help-box {
    margin-top: 14px;
    padding: 12px;
    background: rgba(22, 184, 255, 0.06);
    border: 1px dashed rgba(22, 184, 255, 0.25);
    border-radius: 10px;
    font-size: 0.8rem;
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .sidebar-help-box {
    background: rgba(7, 155, 232, 0.04);
    border-color: rgba(7, 155, 232, 0.2);
    color: #475569;
  }

  /* Main Privacy Content Cards */
  .privacy-section-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    scroll-margin-top: 100px;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }
  html.light-mode .privacy-section-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  }
  .privacy-section-card:hover {
    border-color: rgba(22, 184, 255, 0.35);
  }
  .privacy-section-card.section-target {
    animation: highlightGlow 2s ease-out;
  }
  @keyframes highlightGlow {
    0% { border-color: var(--rx-primary, #16B8FF); box-shadow: 0 0 25px rgba(22, 184, 255, 0.4); }
    100% { border-color: var(--rx-border, #222A3D); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25); }
  }

  .section-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--rx-border-light, rgba(34, 42, 61, 0.7));
  }
  html.light-mode .section-card-header {
    border-bottom-color: #E2E8F0;
  }
  .section-card-title-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .section-num-pill {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(22, 184, 255, 0.15), rgba(104, 87, 255, 0.15));
    border: 1px solid rgba(22, 184, 255, 0.3);
    color: var(--rx-primary, #16B8FF);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    flex-shrink: 0;
  }
  html.light-mode .section-num-pill {
    background: linear-gradient(135deg, rgba(7, 155, 232, 0.1), rgba(103, 87, 232, 0.08));
    border-color: rgba(7, 155, 232, 0.25);
    color: #0284c7;
  }
  .section-card-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin: 0;
    line-height: 1.3;
  }
  html.light-mode .section-card-title { color: #0F172A; }
  .btn-copy-anchor {
    background: transparent;
    border: none;
    color: var(--rx-text-muted, #6F7890);
    font-size: 0.95rem;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 0.6;
  }
  .privacy-section-card:hover .btn-copy-anchor { opacity: 1; }
  .btn-copy-anchor:hover {
    color: var(--rx-primary, #16B8FF);
    background: rgba(22, 184, 255, 0.1);
  }

  /* Body Typography */
  .privacy-card-body {
    font-size: 0.98rem;
    line-height: 1.78;
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .privacy-card-body { color: #334155; }
  .privacy-card-body p { margin-bottom: 14px; }
  .privacy-card-body p:last-child { margin-bottom: 0; }

  /* Subheadings */
  .privacy-subheading {
    font-size: 1.08rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin: 22px 0 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .privacy-subheading { color: #0F172A; }

  /* Custom Lists */
  .privacy-list {
    list-style: none;
    padding-left: 0;
    margin: 14px 0 16px;
  }
  .privacy-list li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 10px;
    line-height: 1.65;
  }
  .privacy-list li::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 10px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 8px rgba(22, 184, 255, 0.5);
  }

  /* Sub-Section Cards (e.g. for Information We Collect 2.1 to 2.5) */
  .privacy-subcard {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 16px;
  }
  html.light-mode .privacy-subcard {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .privacy-subcard-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .privacy-subcard-title { color: #0F172A; }

  /* Callouts */
  .privacy-callout {
    border-radius: 12px;
    padding: 16px 20px;
    margin: 16px 0;
    display: flex;
    gap: 14px;
    align-items: flex-start;
  }
  .callout-info {
    background: rgba(22, 184, 255, 0.07);
    border: 1px solid rgba(22, 184, 255, 0.25);
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .callout-info {
    background: rgba(7, 155, 232, 0.05);
    border-color: rgba(7, 155, 232, 0.2);
    color: #334155;
  }
  .callout-warning {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .callout-warning {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
    color: #7f1d1d;
  }
  .callout-success {
    background: rgba(34, 197, 94, 0.08);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .callout-success {
    background: rgba(34, 197, 94, 0.05);
    border-color: rgba(34, 197, 94, 0.2);
    color: #14532d;
  }
  .callout-icon {
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 1px;
  }
  .callout-info .callout-icon { color: var(--rx-primary, #16B8FF); }
  .callout-warning .callout-icon { color: #EF4444; }
  .callout-success .callout-icon { color: #22C55E; }

  /* Retention Table / Grid */
  .retention-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    margin: 16px 0;
  }
  .retention-item {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    padding: 14px;
  }
  html.light-mode .retention-item {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .retention-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  html.light-mode .retention-title { color: #0F172A; }
  .retention-desc {
    font-size: 0.84rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.5;
  }

  /* Security Pillars Grid */
  .security-pillars-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin: 16px 0;
  }
  .security-pillar-item {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.88rem;
    color: var(--rx-text, #F5F7FF);
    font-weight: 500;
  }
  html.light-mode .security-pillar-item {
    background: #F8FAFC;
    border-color: #E2E8F0;
    color: #1E293B;
  }
  .security-pillar-item i {
    color: var(--rx-primary, #16B8FF);
    font-size: 1.15rem;
    flex-shrink: 0;
  }

  /* User Rights Grid */
  .rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    margin: 16px 0;
  }
  .right-card {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    padding: 14px;
  }
  html.light-mode .right-card {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .right-card-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  html.light-mode .right-card-title { color: #0F172A; }
  .right-card p {
    font-size: 0.84rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.5;
  }

  /* Sharing Category Cards */
  .sharing-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 14px;
    margin: 16px 0;
  }
  .sharing-cat-card {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    padding: 16px;
  }
  html.light-mode .sharing-cat-card {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .sharing-cat-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  html.light-mode .sharing-cat-title { color: #0F172A; }
  .sharing-cat-card p {
    font-size: 0.85rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.5;
  }

  /* Floating Back to Top Button */
  #btnBackToTop {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--rx-surface-elevated, #171E30);
    border: 1px solid var(--rx-border-hover, #304A70);
    color: var(--rx-primary, #16B8FF);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 999;
  }
  html.light-mode #btnBackToTop {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #0284c7;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
  }
  #btnBackToTop.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }
  #btnBackToTop:hover {
    background: var(--rx-primary, #16B8FF);
    color: #ffffff;
    border-color: var(--rx-primary, #16B8FF);
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(22, 184, 255, 0.4);
  }

  /* Toast Notification */
  .privacy-toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #111625;
    border: 1px solid #16B8FF;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 500;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
    z-index: 99999;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .privacy-toast.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
  }

  /* Responsive Adjustments */
  @media (max-width: 991.98px) {
    .privacy-sidebar {
      position: static;
      max-height: none;
      margin-bottom: 2rem;
    }
    .privacy-toc-list {
      max-height: 280px;
    }
    .privacy-section-card {
      padding: 22px 20px;
    }
  }

  /* Print Stylesheet */
  @media print {
    body { background: #ffffff !important; color: #000000 !important; font-size: 11pt !important; }
    .rx-navbar, .rx-footer, .privacy-hero-actions, .privacy-sidebar, #btnBackToTop, #privacyProgressBar, .btn-copy-anchor {
      display: none !important;
    }
    .privacy-section-card {
      border: 1px solid #ddd !important;
      box-shadow: none !important;
      page-break-inside: avoid;
      margin-bottom: 20px !important;
      background: #ffffff !important;
      color: #000000 !important;
    }
    .privacy-hero-wrap {
      background: none !important;
      border: none !important;
      padding: 1rem 0 !important;
    }
    .privacy-hero-title span {
      -webkit-text-fill-color: #000000 !important;
      color: #000000 !important;
    }
  }
</style>

<!-- ============================================================
     HERO HEADER SECTION
     ============================================================ -->
<section class="privacy-hero-wrap">
  <div class="container text-center">
    
    <!-- Badge -->
    <div>
      <span class="privacy-badge-pill">
        <i class="bi bi-shield-check"></i> Data Protection &amp; Privacy Standards
      </span>
    </div>

    <!-- Main Title -->
    <h1 class="privacy-hero-title">
      Privacy <span>Policy</span>
    </h1>

    <!-- Subtitle / Lead -->
    <p class="privacy-hero-lead">
      At TOPUPERS, we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, store, protect, and share information when you visit our website, place an order, contact our support team, or interact with our official social media pages.
    </p>

    <!-- Metadata Badges -->
    <div class="privacy-meta-strip">
      <div class="privacy-meta-item">
        <i class="bi bi-calendar-event"></i>
        <span>Effective Date: <strong>26 October 2026</strong></span>
      </div>
      <div class="privacy-meta-item">
        <i class="bi bi-clock-history"></i>
        <span>Last Updated: <strong>26 October 2026</strong></span>
      </div>
      <div class="privacy-meta-item">
        <i class="bi bi-building"></i>
        <span>Business: <strong>TOPUPERS</strong></span>
      </div>
      <div class="privacy-meta-item">
        <i class="bi bi-globe2"></i>
        <span>Website: <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" style="color:var(--rx-primary); text-decoration:none;"><strong>topupers.com</strong></a></span>
      </div>
      <div class="privacy-meta-item">
        <i class="bi bi-envelope"></i>
        <span>Contact: <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary); text-decoration:none;"><strong><?= e($siteEmail) ?></strong></a></span>
      </div>
      <div class="privacy-meta-item">
        <span class="status-pulse-dot"></span>
        <span style="color:#22C55E; font-weight:600;">Active Policy</span>
      </div>
    </div>

    <!-- Quick Search & Actions Toolbar -->
    <div class="privacy-hero-actions">
      <div class="privacy-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="privacySearchInput" placeholder="Search privacy topics (e.g., cookies, deletion, payment)..." aria-label="Search privacy policy">
      </div>
      <button type="button" class="privacy-btn-action" onclick="window.print()" title="Print or save as PDF">
        <i class="bi bi-printer"></i>
        <span>Print Document</span>
      </button>
    </div>

  </div>
</section>

<!-- ============================================================
     MAIN BODY & TABLE OF CONTENTS
     ============================================================ -->
<div class="privacy-container-wrap">
  <div class="container">

    <!-- Executive Summary / Core Highlights Cards -->
    <div class="privacy-highlights-grid">
      <div class="privacy-highlight-card">
        <div class="highlight-icon-wrap icon-teal">
          <i class="bi bi-shield-check"></i>
        </div>
        <h4>Data Minimization</h4>
        <p>We only collect information strictly needed to process game top-ups, verify transactions, and prevent fraud.</p>
      </div>

      <div class="privacy-highlight-card">
        <div class="highlight-icon-wrap icon-green">
          <i class="bi bi-slash-circle-fill"></i>
        </div>
        <h4>No Selling of Personal Data</h4>
        <p>TOPUPERS does not sell customer personal information, phone numbers, or emails to advertisers or third-party marketers.</p>
      </div>

      <div class="privacy-highlight-card">
        <div class="highlight-icon-wrap icon-purple">
          <i class="bi bi-lock-fill"></i>
        </div>
        <h4>Never Ask for Passwords / OTPs</h4>
        <p>UID-based top-ups require only your Player ID and Server/Zone ID. We never ask for game passwords, recovery codes, or payment PINs.</p>
      </div>

      <div class="privacy-highlight-card">
        <div class="highlight-icon-wrap icon-amber">
          <i class="bi bi-person-x-fill"></i>
        </div>
        <h4>Your Rights &amp; Deletion</h4>
        <p>You have the right to request access, correction, or deletion of your account and eligible personal information anytime.</p>
      </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4">

      <!-- ==========================================
           LEFT COLUMN: Sticky Table of Contents
           ========================================== -->
      <div class="col-lg-4 col-xl-3">
        <nav class="privacy-sidebar" id="privacyStickyNav" aria-label="Privacy policy navigation">
          
          <div class="privacy-sidebar-header">
            <h3 class="privacy-sidebar-title">
              <i class="bi bi-list-nested text-primary"></i>
              <span>Navigation</span>
            </h3>
            <span class="privacy-count-badge">18 Sections</span>
          </div>

          <ul class="privacy-toc-list" id="privacyTocList">
            <li><a href="#section-1" class="privacy-toc-link"><span class="toc-index">01</span><span>Introduction</span></a></li>
            <li><a href="#section-2" class="privacy-toc-link"><span class="toc-index">02</span><span>Information We Collect</span></a></li>
            <li><a href="#section-3" class="privacy-toc-link"><span class="toc-index">03</span><span>How We Use Your Information</span></a></li>
            <li><a href="#section-4" class="privacy-toc-link"><span class="toc-index">04</span><span>Legal Basis and Consent</span></a></li>
            <li><a href="#section-5" class="privacy-toc-link"><span class="toc-index">05</span><span>How We Share Information</span></a></li>
            <li><a href="#section-6" class="privacy-toc-link"><span class="toc-index">06</span><span>Third-Party Services</span></a></li>
            <li><a href="#section-7" class="privacy-toc-link"><span class="toc-index">07</span><span>International Data Transfers</span></a></li>
            <li><a href="#section-8" class="privacy-toc-link"><span class="toc-index">08</span><span>Data Storage and Retention</span></a></li>
            <li><a href="#section-9" class="privacy-toc-link"><span class="toc-index">09</span><span>Information Security</span></a></li>
            <li><a href="#section-10" class="privacy-toc-link"><span class="toc-index">10</span><span>Your Privacy Rights</span></a></li>
            <li><a href="#section-11" class="privacy-toc-link"><span class="toc-index">11</span><span>Account Deletion Requests</span></a></li>
            <li><a href="#section-12" class="privacy-toc-link"><span class="toc-index">12</span><span>Marketing Communications</span></a></li>
            <li><a href="#section-13" class="privacy-toc-link"><span class="toc-index">13</span><span>Children's Privacy</span></a></li>
            <li><a href="#section-14" class="privacy-toc-link"><span class="toc-index">14</span><span>Social Media &amp; Messenger</span></a></li>
            <li><a href="#section-15" class="privacy-toc-link"><span class="toc-index">15</span><span>Reviews, Testimonials &amp; Proof</span></a></li>
            <li><a href="#section-16" class="privacy-toc-link"><span class="toc-index">16</span><span>Data Breaches &amp; Incidents</span></a></li>
            <li><a href="#section-17" class="privacy-toc-link"><span class="toc-index">17</span><span>Changes to This Policy</span></a></li>
            <li><a href="#section-18" class="privacy-toc-link"><span class="toc-index">18</span><span>Contact Us</span></a></li>
          </ul>

          <div class="sidebar-help-box">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="bi bi-shield-lock text-primary"></i>
              <strong style="color:var(--rx-text);">Privacy Officer</strong>
            </div>
            <p class="mb-2" style="font-size:0.78rem;">Questions regarding your data or want to request deletion? Reach out directly.</p>
            <a href="mailto:<?= e($siteEmail) ?>" class="btn btn-sm btn-outline-primary w-100" style="border-radius:6px; font-size:0.78rem;">Email Privacy Team</a>
          </div>

        </nav>
      </div>

      <!-- ==========================================
           RIGHT COLUMN: Detailed Policy Sections
           ========================================== -->
      <div class="col-lg-8 col-xl-9">

        <!-- Section 1 -->
        <article class="privacy-section-card" id="section-1">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">01</div>
              <h2 class="section-card-title">Introduction</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-1')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>At <strong>TOPUPERS</strong>, we respect your privacy and are committed to protecting your personal information.</p>
            <p>This Privacy Policy explains how we collect, use, store, protect, and share information when you visit our website, place an order, contact our support team, or interact with our official social media pages.</p>
            <p>TOPUPERS provides gaming top-ups and digital products, including supported game currencies, passes, subscriptions, redeem codes, and related services.</p>
            <p>By using our services, you acknowledge this Privacy Policy. Where applicable, we will request consent or establish another lawful basis before processing your personal information.</p>
          </div>
        </article>

        <!-- Section 2 -->
        <article class="privacy-section-card" id="section-2">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">02</div>
              <h2 class="section-card-title">Information We Collect</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-2')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>We collect only the information reasonably necessary to operate our services, process orders, provide customer support, and protect against fraud.</p>

            <!-- 2.1 -->
            <div class="privacy-subcard">
              <div class="privacy-subcard-title">
                <i class="bi bi-person-badge text-primary"></i>
                <span>2.1 Personal and Contact Information</span>
              </div>
              <p>Depending on how you use our services, we may collect:</p>
              <ul class="privacy-list mb-0">
                <li>Name or display name.</li>
                <li>Email address.</li>
                <li>Mobile phone number.</li>
                <li>Account information needed to register or sign in to our website.</li>
                <li>Customer support messages and relevant communications.</li>
              </ul>
            </div>

            <!-- 2.2 -->
            <div class="privacy-subcard">
              <div class="privacy-subcard-title">
                <i class="bi bi-controller text-info"></i>
                <span>2.2 Gaming Information</span>
              </div>
              <p>To deliver eligible products, we may collect:</p>
              <ul class="privacy-list">
                <li>Game ID or Player ID.</li>
                <li>Server ID, Zone ID, or region.</li>
                <li>In-game name (IGN).</li>
                <li>Selected game, product, denomination, and package.</li>
                <li>Relevant order and delivery status.</li>
              </ul>
              <div class="privacy-callout callout-warning mb-0">
                <i class="bi bi-shield-slash-fill callout-icon"></i>
                <div>
                  <strong>Critical Password Protection Notice:</strong> We do not ordinarily need your game account password, OTP, or recovery codes to process a UID-based top-up. Please never share these credentials with us.
                </div>
              </div>
            </div>

            <!-- 2.3 -->
            <div class="privacy-subcard">
              <div class="privacy-subcard-title">
                <i class="bi bi-credit-card-2-front text-success"></i>
                <span>2.3 Payment and Transaction Information</span>
              </div>
              <p>Depending on the payment method, we may process:</p>
              <ul class="privacy-list">
                <li>Order number and order amount.</li>
                <li>Payment method.</li>
                <li>Transaction ID or payment reference.</li>
                <li>Payment status and verification results.</li>
                <li>Relevant payment receipts or screenshots voluntarily submitted for verification.</li>
                <li>Refund records and payment-related support communications.</li>
              </ul>
              <div class="privacy-callout callout-warning mb-2">
                <i class="bi bi-exclamation-triangle-fill callout-icon"></i>
                <div>
                  <strong>Payment Authentication Credentials:</strong> Payment credentials, PINs, OTPs, and other authentication secrets should never be sent to TOPUPERS through customer support.
                </div>
              </div>
              <p class="mb-0" style="font-size:0.9rem; color:var(--rx-text-muted);">Where payment processing is handled by a third-party provider, that provider may independently collect and process information under its own privacy policy.</p>
            </div>

            <!-- 2.4 -->
            <div class="privacy-subcard">
              <div class="privacy-subcard-title">
                <i class="bi bi-cpu text-purple"></i>
                <span>2.4 Technical Information</span>
              </div>
              <p>When you visit our website, we or our service providers may collect certain technical information, where enabled and permitted, including:</p>
              <ul class="privacy-list">
                <li>IP address.</li>
                <li>Browser type and version.</li>
                <li>Device and operating system information.</li>
                <li>Website access times and basic diagnostic logs.</li>
                <li>Security events and suspicious activity indicators.</li>
              </ul>
              <p class="mb-0">This information may be used to maintain website reliability, diagnose errors, protect accounts, and prevent abuse.</p>
            </div>

            <!-- 2.5 -->
            <div class="privacy-subcard mb-0">
              <div class="privacy-subcard-title">
                <i class="bi bi-cookie text-amber"></i>
                <span>2.5 Cookies and Similar Technologies</span>
              </div>
              <p>Our website may use cookies or similar technologies to support essential functions such as login sessions, shopping or checkout workflows, security, and user preferences.</p>
              <p>If analytics or advertising technologies are enabled, they may collect additional information according to their configuration and applicable consent requirements.</p>
              <p>You can manage cookies through your browser settings. Disabling essential cookies may affect certain website features.</p>
              <p class="mb-0">We will describe any non-essential tracking technologies we actually use and obtain consent where required.</p>
            </div>

          </div>
        </article>

        <!-- Section 3 -->
        <article class="privacy-section-card" id="section-3">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">03</div>
              <h2 class="section-card-title">How We Use Your Information</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-3')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>TOPUPERS may use collected information to:</p>
            <ul class="privacy-list">
              <li>Create and manage customer accounts.</li>
              <li>Accept, process, and fulfil orders.</li>
              <li>Deliver gaming top-ups and digital products.</li>
              <li>Verify payments and investigate failed transactions.</li>
              <li>Process eligible cancellations and refunds.</li>
              <li>Respond to customer enquiries and complaints.</li>
              <li>Detect suspicious payments, fraud, and unauthorized activity.</li>
              <li>Maintain website security and improve service reliability.</li>
              <li>Send necessary order confirmations, delivery updates, and support messages.</li>
              <li>Send promotional communications where permitted and, where required, with your consent.</li>
              <li>Maintain transaction records and comply with applicable legal obligations.</li>
              <li>Protect the legitimate rights and interests of TOPUPERS and its customers.</li>
            </ul>
            <div class="privacy-callout callout-info">
              <i class="bi bi-check2-circle callout-icon"></i>
              <div>
                <strong>Purpose Compatibility:</strong> We will not knowingly use personal information for purposes incompatible with the purpose for which it was collected without an appropriate lawful basis.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 4 -->
        <article class="privacy-section-card" id="section-4">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">04</div>
              <h2 class="section-card-title">Legal Basis and Consent</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-4')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>TOPUPERS will process personal information in accordance with applicable laws of Bangladesh.</p>
            <p>Depending on the purpose and circumstances, processing may rely on your consent, steps necessary to fulfil an order or provide a requested service, legal obligations, or another lawful basis permitted by applicable law.</p>
            <p>Where consent is required, we will explain the relevant purpose and request consent before processing. You may withdraw consent where legally applicable, although this will not invalidate processing lawfully carried out before withdrawal.</p>
            <div class="privacy-callout callout-warning">
              <i class="bi bi-info-circle-fill callout-icon"></i>
              <div>
                <strong>Essential Information Notice:</strong> If you decline to provide information that is essential to fulfil an order (such as your Player ID or payment reference), we may be unable to provide that particular service.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 5 -->
        <article class="privacy-section-card" id="section-5">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">05</div>
              <h2 class="section-card-title">How We Share Information</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-5')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            
            <div class="privacy-callout callout-success mb-3">
              <i class="bi bi-shield-fill-check callout-icon"></i>
              <div>
                <strong>Zero Advertiser Data Sales:</strong> TOPUPERS does not sell customers' personal information to advertisers or marketing aggregators.
              </div>
            </div>

            <p>We may share limited information with the following categories of recipients where necessary and lawful:</p>

            <div class="sharing-categories-grid">
              <div class="sharing-cat-card">
                <div class="sharing-cat-title">
                  <i class="bi bi-controller text-primary"></i>
                  <span>5.1 Gaming Top-Up Suppliers</span>
                </div>
                <p>We may share the game identifiers, product details, and order information required by a supplier or fulfilment partner to complete a purchase.</p>
              </div>

              <div class="sharing-cat-card">
                <div class="sharing-cat-title">
                  <i class="bi bi-credit-card text-success"></i>
                  <span>5.2 Payment Service Providers</span>
                </div>
                <p>We may share relevant transaction details with payment providers to receive payments, verify transactions, prevent fraud, or process refunds.</p>
              </div>

              <div class="sharing-cat-card">
                <div class="sharing-cat-title">
                  <i class="bi bi-hdd-network text-info"></i>
                  <span>5.3 Website &amp; Technical Providers</span>
                </div>
                <p>Hosting providers, security services, email providers, database services, and other technical providers may process information on our behalf to operate and secure our services.</p>
              </div>

              <div class="sharing-cat-card">
                <div class="sharing-cat-title">
                  <i class="bi bi-bank text-warning"></i>
                  <span>5.4 Legal &amp; Regulatory Authorities</span>
                </div>
                <p>We may disclose information when required by law, a valid legal process, or a competent authority, or when necessary to address suspected fraud or protect lawful rights.</p>
              </div>
            </div>

            <p class="mb-0">We will seek to limit disclosures to information reasonably necessary for the relevant purpose.</p>
          </div>
        </article>

        <!-- Section 6 -->
        <article class="privacy-section-card" id="section-6">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">06</div>
              <h2 class="section-card-title">Third-Party Services</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-6')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>Our services may rely on third-party platforms, including payment processors, gaming suppliers, social media platforms, hosting providers, and email services.</p>
            <p>These providers may process information under their own terms and privacy policies. TOPUPERS does not control every aspect of their privacy or security practices.</p>
            <p>Customers should review the applicable policies when interacting directly with third-party services.</p>
          </div>
        </article>

        <!-- Section 7 -->
        <article class="privacy-section-card" id="section-7">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">07</div>
              <h2 class="section-card-title">International Data Transfers</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-7')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>Some suppliers or technical service providers may process information outside Bangladesh.</p>
            <p>Where personal information is transferred internationally, TOPUPERS will take the measures required by applicable law, including any applicable restrictions, safeguards, consent requirements, and contractual protections.</p>
            <p>We will not assume that a service provider is permitted to receive personal information internationally merely because it offers services to TOPUPERS.</p>
          </div>
        </article>

        <!-- Section 8 -->
        <article class="privacy-section-card" id="section-8">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">08</div>
              <h2 class="section-card-title">Data Storage and Retention</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-8')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>We retain personal information only for as long as reasonably necessary for the purposes described in this policy, subject to applicable legal requirements.</p>
            <p>Retention periods may vary by information type:</p>

            <div class="retention-grid">
              <div class="retention-item">
                <div class="retention-title"><i class="bi bi-person-circle text-primary"></i> Account Information</div>
                <p class="retention-desc">Retained while the account remains active and for any additional period required for legitimate or legal purposes.</p>
              </div>

              <div class="retention-item">
                <div class="retention-title"><i class="bi bi-receipt text-success"></i> Order &amp; Transactions</div>
                <p class="retention-desc">For the period necessary for order support, accounting, dispute resolution, fraud prevention, and legal obligations.</p>
              </div>

              <div class="retention-item">
                <div class="retention-title"><i class="bi bi-cash-stack text-warning"></i> Payment Evidence</div>
                <p class="retention-desc">For as long as necessary to verify transactions, investigate disputes, and meet applicable requirements.</p>
              </div>

              <div class="retention-item">
                <div class="retention-title"><i class="bi bi-chat-dots text-info"></i> Support Communications</div>
                <p class="retention-desc">For as long as reasonably necessary to resolve enquiries and maintain relevant customer support records.</p>
              </div>

              <div class="retention-item">
                <div class="retention-title"><i class="bi bi-file-earmark-text text-purple"></i> Security &amp; Diagnostic Logs</div>
                <p class="retention-desc">For a limited period appropriate to system security, troubleshooting, and legal compliance.</p>
              </div>
            </div>

            <p>TOPUPERS will establish appropriate retention periods based on actual operational and legal needs. When information is no longer required, we will securely delete it, anonymize it, or otherwise dispose of it as permitted by law.</p>
            <p class="mb-0">Some records may need to be retained after an account deletion request where a legal obligation or legitimate unresolved dispute requires it.</p>
          </div>
        </article>

        <!-- Section 9 -->
        <article class="privacy-section-card" id="section-9">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">09</div>
              <h2 class="section-card-title">Information Security</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-9')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>We will implement appropriate technical and organizational measures designed to protect personal information against unauthorized access, disclosure, alteration, loss, destruction, and misuse.</p>
            <p>Depending on the system and information involved, safeguards may include:</p>

            <div class="security-pillars-grid">
              <div class="security-pillar-item"><i class="bi bi-key-fill"></i> Restricted access to administrative systems</div>
              <div class="security-pillar-item"><i class="bi bi-shield-lock-fill"></i> Strong authentication &amp; password protection</div>
              <div class="security-pillar-item"><i class="bi bi-lock-fill"></i> HTTPS encryption where properly configured</div>
              <div class="security-pillar-item"><i class="bi bi-patch-check-fill"></i> Security updates &amp; vulnerability management</div>
              <div class="security-pillar-item"><i class="bi bi-database-fill-lock"></i> Appropriate database access controls</div>
              <div class="security-pillar-item"><i class="bi bi-arrow-repeat"></i> Backups and disaster recovery procedures</div>
              <div class="security-pillar-item"><i class="bi bi-activity"></i> Monitoring &amp; suspicious activity investigations</div>
              <div class="security-pillar-item"><i class="bi bi-building-check"></i> Contractual safeguards with service providers</div>
            </div>

            <p class="mb-0">No website, electronic transmission, or storage system can be guaranteed to be completely secure. However, we will take reasonable measures to reduce risks and respond appropriately to security incidents.</p>
          </div>
        </article>

        <!-- Section 10 -->
        <article class="privacy-section-card" id="section-10">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">10</div>
              <h2 class="section-card-title">Your Privacy Rights</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-10')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>Subject to applicable law and any relevant exceptions, you may have rights concerning your personal information, including the right to:</p>

            <div class="rights-grid">
              <div class="right-card">
                <div class="right-card-title"><i class="bi bi-info-circle text-primary"></i> Transparency</div>
                <p>Request information about how your personal data is processed.</p>
              </div>
              <div class="right-card">
                <div class="right-card-title"><i class="bi bi-eye text-info"></i> Access</div>
                <p>Request access to personal information held about you.</p>
              </div>
              <div class="right-card">
                <div class="right-card-title"><i class="bi bi-pencil-square text-success"></i> Correction</div>
                <p>Request correction of inaccurate or incomplete information.</p>
              </div>
              <div class="right-card">
                <div class="right-card-title"><i class="bi bi-trash3 text-danger"></i> Deletion / Erasure</div>
                <p>Request deletion or erasure where legally applicable.</p>
              </div>
              <div class="right-card">
                <div class="right-card-title"><i class="bi bi-x-circle text-warning"></i> Withdraw Consent</div>
                <p>Withdraw consent where processing relies on consent.</p>
              </div>
              <div class="right-card">
                <div class="right-card-title"><i class="bi bi-megaphone text-purple"></i> Raise Concerns</div>
                <p>Submit a concern or complaint regarding data handling.</p>
              </div>
            </div>

            <p>Exercise any additional rights provided by applicable law.</p>
            
            <div class="privacy-callout callout-info mb-0">
              <i class="bi bi-envelope-at callout-icon"></i>
              <div>
                <strong>How to Exercise Your Rights:</strong> Contact our team at <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary); font-weight:600;"><?= e($siteEmail) ?></a>. We may need to verify your identity before responding. We will handle requests in accordance with applicable law and explain any lawful reason why a request cannot be fully granted.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 11 -->
        <article class="privacy-section-card" id="section-11">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">11</div>
              <h2 class="section-card-title">Account Deletion Requests</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-11')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>Customers may request deletion of their TOPUPERS account by contacting our official support channel.</p>
            <p>After verifying the request, we will review the account information and delete or anonymize eligible information in accordance with applicable law and our retention requirements.</p>
            <p>Certain transaction records may need to be retained for legal compliance, accounting, payment disputes, fraud prevention, or other lawful purposes.</p>
            <p>Deleting an account does not automatically reverse completed orders or guarantee that every record held independently by a third-party payment provider, supplier, or social media platform will be deleted.</p>
            <p class="mb-0">Where appropriate, we will explain what information remains and why it must be retained.</p>
          </div>
        </article>

        <!-- Section 12 -->
        <article class="privacy-section-card" id="section-12">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">12</div>
              <h2 class="section-card-title">Marketing Communications</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-12')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>TOPUPERS may provide promotional offers, product updates, discounts, and announcements through email or other supported communication channels where permitted.</p>
            <p>Where required, we will obtain your consent before sending marketing communications.</p>
            <p>You may opt out of promotional messages using the available unsubscribe method or by contacting support.</p>
            <div class="privacy-callout callout-info mb-0">
              <i class="bi bi-bell-fill callout-icon"></i>
              <div>
                <strong>Essential Transactional Messages:</strong> Opting out of marketing will not prevent us from sending essential service communications, such as order confirmations, payment updates, security notices, and refund information.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 13 -->
        <article class="privacy-section-card" id="section-13">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">13</div>
              <h2 class="section-card-title">Children's Privacy</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-13')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>Our services are not intended to encourage children to provide unnecessary personal information.</p>
            <p>Where a customer is below the applicable age for independently providing consent or entering into a transaction, appropriate parental or guardian permission will be required where applicable.</p>
            <p class="mb-0">If we become aware that personal information has been collected unlawfully from a child, we will review the circumstances and take appropriate action in accordance with applicable law.</p>
          </div>
        </article>

        <!-- Section 14 -->
        <article class="privacy-section-card" id="section-14">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">14</div>
              <h2 class="section-card-title">Social Media and Messenger Orders</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-14')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>Customers may contact TOPUPERS through Facebook, Messenger, WhatsApp, or other official communication channels that we make available.</p>
            <p>Information shared through these platforms may also be processed by the relevant platform operator under its own privacy policy.</p>
            <div class="privacy-callout callout-warning mb-3">
              <i class="bi bi-shield-exclamation callout-icon"></i>
              <div>
                <strong>Message Hygiene:</strong> Please avoid sending passwords, OTPs, full payment credentials, or unrelated sensitive information in messages.
              </div>
            </div>
            <p class="mb-0">If order screenshots or testimonials are shared publicly, TOPUPERS should remove or obscure identifying information such as customer names, phone numbers, transaction references, and other private details unless appropriate authorization has been obtained.</p>
          </div>
        </article>

        <!-- Section 15 -->
        <article class="privacy-section-card" id="section-15">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">15</div>
              <h2 class="section-card-title">Public Reviews, Testimonials and Order Proof</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-15')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>TOPUPERS may wish to publish customer reviews, feedback, or order completion proof to demonstrate service quality.</p>
            <p>We will seek appropriate permission before publishing identifiable customer testimonials or personal details where required.</p>
            <p>Whenever practical, order proof should be anonymized by hiding phone numbers, transaction identifiers, email addresses, and other unnecessary personal information.</p>
            <p class="mb-0">Customers may contact support to raise concerns about the publication of their personal information.</p>
          </div>
        </article>

        <!-- Section 16 -->
        <article class="privacy-section-card" id="section-16">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">16</div>
              <h2 class="section-card-title">Data Breaches and Security Incidents</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-16')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>If TOPUPERS becomes aware of a personal data breach, we will investigate the incident, take appropriate steps to contain and address it, and provide notifications to affected individuals or competent authorities where required by applicable law.</p>
            <p class="mb-0">We will take reasonable measures to prevent recurrence and protect affected customer information.</p>
          </div>
        </article>

        <!-- Section 17 -->
        <article class="privacy-section-card" id="section-17">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">17</div>
              <h2 class="section-card-title">Changes to This Privacy Policy</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-17')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>We may update this Privacy Policy when our services, technical systems, business practices, or applicable laws change.</p>
            <p>The updated version will be published with a revised <strong>“Last Updated”</strong> date. Where required, we will provide notice or seek renewed consent for material changes.</p>
            <p class="mb-0">Customers should review this page periodically to understand how their information is handled.</p>
          </div>
        </article>

        <!-- Section 18 -->
        <article class="privacy-section-card" id="section-18">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">18</div>
              <h2 class="section-card-title">Contact Us</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-18')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="privacy-card-body">
            <p>For privacy questions, access or correction requests, account deletion requests, or complaints regarding personal information, please contact:</p>

            <div class="p-3 rounded-3 mt-3 mb-3" style="background:var(--rx-surface-hover); border:1px solid var(--rx-border);">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                  <h4 style="font-size:1.05rem; font-weight:700; color:var(--rx-text); margin:0 0 4px;">TOPUPERS Privacy Desk</h4>
                  <p class="mb-1" style="font-size:0.88rem; color:var(--rx-text-secondary);">
                    <strong>Email:</strong> <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary);"><?= e($siteEmail) ?></a>
                  </p>
                  <p class="mb-1" style="font-size:0.88rem; color:var(--rx-text-secondary);">
                    <strong>Website:</strong> <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" style="color:var(--rx-primary);">https://topupers.com</a>
                  </p>
                  <p class="mb-0" style="font-size:0.88rem; color:var(--rx-text-secondary);">
                    <strong>Support Channel:</strong> Official TOPUPERS Facebook page or the contact method published on our website.
                  </p>
                </div>
                <div class="d-flex gap-2">
                  <a href="mailto:<?= e($siteEmail) ?>" class="btn btn-primary btn-sm px-3" style="border-radius:8px;">
                    <i class="bi bi-envelope-fill me-1"></i> Send Email
                  </a>
                  <a href="<?= APP_URL ?>/contact.php" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:8px;">
                    <i class="bi bi-headset me-1"></i> Help Center
                  </a>
                </div>
              </div>
            </div>

            <p class="mb-3" style="font-size:0.92rem;">Please include sufficient information to help us identify your request, but do not send passwords, OTPs, or full payment authentication credentials.</p>

            <div class="text-center pt-3 border-top" style="border-color:var(--rx-border-light) !important;">
              <p class="mb-0 text-muted" style="font-size:0.85rem;">
                <strong>TOPUPERS — Gaming Top-Up &amp; Digital Store</strong> &bull; All Rights Reserved &bull; Effective Date: 26 October 2026
              </p>
            </div>
          </div>
        </article>

      </div>
    </div>

  </div>
</div>

<!-- Back to Top Button -->
<button type="button" id="btnBackToTop" aria-label="Back to top of page" title="Back to top">
  <i class="bi bi-chevron-up"></i>
</button>

<!-- Copy Link Toast Notification -->
<div id="privacyToast" class="privacy-toast">
  <i class="bi bi-check2-circle text-primary"></i>
  <span>Section link copied to clipboard!</span>
</div>

<!-- Interactive Client-side Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Reading Progress Bar
  const progressBar = document.getElementById('privacyProgressBar');
  window.addEventListener('scroll', () => {
    const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
    if (progressBar) progressBar.style.width = scrolled + '%';
  }, { passive: true });

  // 2. Back to Top Button
  const btnBackToTop = document.getElementById('btnBackToTop');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
      btnBackToTop.classList.add('visible');
    } else {
      btnBackToTop.classList.remove('visible');
    }
  }, { passive: true });
  btnBackToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // 3. Scrollspy for Sticky TOC
  const sections = document.querySelectorAll('.privacy-section-card');
  const tocLinks = document.querySelectorAll('.privacy-toc-link');

  function updateActiveToc() {
    let currentId = '';
    const scrollPos = window.scrollY + 140;

    sections.forEach(section => {
      const top = section.offsetTop;
      const height = section.offsetHeight;
      if (scrollPos >= top && scrollPos < top + height) {
        currentId = section.getAttribute('id');
      }
    });

    if (!currentId && sections.length > 0 && window.scrollY < sections[0].offsetTop) {
      currentId = sections[0].getAttribute('id');
    }

    tocLinks.forEach(link => {
      if (link.getAttribute('href') === '#' + currentId) {
        link.classList.add('active');
        const tocList = document.getElementById('privacyTocList');
        if (tocList && window.innerWidth >= 992) {
          const linkTop = link.offsetTop - tocList.offsetTop;
          if (linkTop < tocList.scrollTop || linkTop > tocList.scrollTop + tocList.clientHeight - 40) {
            tocList.scrollTo({ top: linkTop - 40, behavior: 'smooth' });
          }
        }
      } else {
        link.classList.remove('active');
      }
    });
  }

  window.addEventListener('scroll', updateActiveToc, { passive: true });
  updateActiveToc();

  // 4. Live Search Filtering
  const searchInput = document.getElementById('privacySearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      sections.forEach(sec => {
        const text = sec.innerText.toLowerCase();
        if (!q || text.includes(q)) {
          sec.style.display = 'block';
          sec.style.opacity = '1';
        } else {
          sec.style.display = 'none';
        }
      });

      // Filter TOC links as well
      tocLinks.forEach(link => {
        const targetId = link.getAttribute('href').replace('#', '');
        const targetSec = document.getElementById(targetId);
        if (targetSec && targetSec.style.display === 'none') {
          link.parentElement.style.display = 'none';
        } else {
          link.parentElement.style.display = 'block';
        }
      });
    });
  }

  // 5. URL Hash Deep Link Auto Scroll
  if (window.location.hash) {
    const target = document.querySelector(window.location.hash);
    if (target) {
      setTimeout(() => {
        target.scrollIntoView({ behavior: 'smooth' });
        target.classList.add('section-target');
      }, 300);
    }
  }
});

// Copy Anchor Link to Clipboard
function copyAnchorLink(sectionId) {
  const url = window.location.origin + window.location.pathname + '#' + sectionId;
  navigator.clipboard.writeText(url).then(() => {
    showToast('Link to Section copied to clipboard!');
  }).catch(() => {
    const input = document.createElement('input');
    input.value = url;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    showToast('Link to Section copied to clipboard!');
  });
}

function showToast(msg) {
  const toast = document.getElementById('privacyToast');
  if (!toast) return;
  toast.querySelector('span').innerText = msg;
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 2400);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
