<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Terms & Conditions — TopUpers';
$pageDescription = 'Official Terms and Conditions for TopUpers gaming top-ups and digital products store in Bangladesh.';

include __DIR__ . '/includes/header.php';

$siteName = !empty(getSetting('site_name')) ? getSetting('site_name') : (defined('APP_NAME') ? APP_NAME : 'TopUpers');
$siteEmail = !empty(getSetting('site_email')) ? getSetting('site_email') : 'support@topupers.com';
?>

<!-- Reading Progress Bar -->
<div id="termsProgressBar" aria-hidden="true"></div>

<!-- Custom Scoped Styles for Modern Terms & Conditions -->
<style>
  /* Reading Progress Bar */
  #termsProgressBar {
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
  .terms-hero-wrap {
    position: relative;
    background: radial-gradient(1000px circle at 50% -20%, rgba(22, 184, 255, 0.12), transparent 70%),
                linear-gradient(180deg, var(--rx-bg-secondary, #0A0E18) 0%, var(--rx-bg, #070A12) 100%);
    border-bottom: 1px solid var(--rx-border, #222A3D);
    padding: 3.5rem 0 3rem;
    overflow: hidden;
  }
  html.light-mode .terms-hero-wrap {
    background: radial-gradient(1000px circle at 50% -20%, rgba(7, 155, 232, 0.08), transparent 70%),
                linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
    border-bottom-color: var(--rx-border, #E1E5EE);
  }
  .terms-hero-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    opacity: 0.6;
  }
  html.light-mode .terms-hero-wrap::before {
    background-image: radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    opacity: 0.8;
  }

  .terms-badge-pill {
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
  html.light-mode .terms-badge-pill {
    background: rgba(7, 155, 232, 0.08);
    border-color: rgba(7, 155, 232, 0.2);
    color: #0284c7;
  }

  .terms-hero-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    letter-spacing: -0.5px;
    color: var(--rx-text, #F5F7FF);
    line-height: 1.15;
    margin-bottom: 1rem;
  }
  .terms-hero-title span {
    background: linear-gradient(135deg, var(--rx-primary, #16B8FF), #A78BFA);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .terms-hero-lead {
    font-size: 1.05rem;
    color: var(--rx-text-secondary, #A5AEC2);
    max-width: 720px;
    margin: 0 auto 2rem;
    line-height: 1.6;
  }

  /* Metadata Chips Bar */
  .terms-meta-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 2rem;
  }
  .terms-meta-item {
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
  html.light-mode .terms-meta-item {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    color: #475569;
  }
  .terms-meta-item strong {
    color: var(--rx-text, #F5F7FF);
    font-weight: 600;
  }
  html.light-mode .terms-meta-item strong {
    color: #0F172A;
  }
  .terms-meta-item i {
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

  /* Hero Action Toolbar */
  .terms-hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .terms-search-box {
    position: relative;
    width: 100%;
    max-width: 380px;
  }
  .terms-search-box input {
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
  html.light-mode .terms-search-box input {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #0F172A;
  }
  .terms-search-box input:focus {
    border-color: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 0 3px rgba(22, 184, 255, 0.15);
  }
  .terms-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rx-text-muted, #6F7890);
    font-size: 0.95rem;
    pointer-events: none;
  }
  .terms-btn-action {
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
  html.light-mode .terms-btn-action {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #1E293B;
  }
  .terms-btn-action:hover {
    background: var(--rx-surface-hover, #151C2D);
    border-color: var(--rx-primary, #16B8FF);
    color: var(--rx-primary, #16B8FF);
    transform: translateY(-1px);
  }

  /* Executive Summary Highlight Cards */
  .terms-highlights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 2.5rem;
  }
  .terms-highlight-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 14px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  html.light-mode .terms-highlight-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
  }
  .terms-highlight-card:hover {
    transform: translateY(-3px);
    border-color: rgba(22, 184, 255, 0.4);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }
  html.light-mode .terms-highlight-card:hover {
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
  
  .terms-highlight-card h4 {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .terms-highlight-card h4 { color: #0F172A; }
  .terms-highlight-card p {
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.55;
  }

  /* Layout Structure */
  .terms-container-wrap {
    padding: 3rem 0 5rem;
  }

  /* Sticky TOC Sidebar */
  .terms-sidebar {
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
  html.light-mode .terms-sidebar {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  }
  .terms-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--rx-border, #222A3D);
  }
  html.light-mode .terms-sidebar-header { border-bottom-color: #E2E8F0; }
  .terms-sidebar-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    font-family: 'Outfit', sans-serif;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  html.light-mode .terms-sidebar-title { color: #0F172A; }
  .terms-count-badge {
    background: rgba(22, 184, 255, 0.12);
    color: var(--rx-primary, #16B8FF);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .terms-toc-list {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    padding-right: 4px;
    flex: 1;
  }
  .terms-toc-list::-webkit-scrollbar {
    width: 4px;
  }
  .terms-toc-list::-webkit-scrollbar-thumb {
    background: var(--rx-border, #222A3D);
    border-radius: 4px;
  }
  .terms-toc-link {
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
  html.light-mode .terms-toc-link { color: #64748B; }
  .terms-toc-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--rx-primary, #16B8FF);
  }
  html.light-mode .terms-toc-link:hover {
    background: rgba(0, 0, 0, 0.04);
    color: var(--rx-primary, #079BE8);
  }
  .terms-toc-link.active {
    background: rgba(22, 184, 255, 0.12);
    color: var(--rx-primary, #16B8FF);
    font-weight: 600;
    border-left: 3px solid var(--rx-primary, #16B8FF);
  }
  html.light-mode .terms-toc-link.active {
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
  .terms-toc-link.active .toc-index {
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

  /* Main Terms Content Cards */
  .terms-section-card {
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
  html.light-mode .terms-section-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  }
  .terms-section-card:hover {
    border-color: rgba(22, 184, 255, 0.35);
  }
  .terms-section-card.section-target {
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
  .terms-section-card:hover .btn-copy-anchor { opacity: 1; }
  .btn-copy-anchor:hover {
    color: var(--rx-primary, #16B8FF);
    background: rgba(22, 184, 255, 0.1);
  }

  /* Section Card Body Typography */
  .terms-card-body {
    font-size: 0.98rem;
    line-height: 1.78;
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .terms-card-body { color: #334155; }
  .terms-card-body p { margin-bottom: 14px; }
  .terms-card-body p:last-child { margin-bottom: 0; }

  /* Custom Clean Bullet Lists */
  .terms-list {
    list-style: none;
    padding-left: 0;
    margin: 14px 0 16px;
  }
  .terms-list li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 10px;
    line-height: 1.65;
  }
  .terms-list li::before {
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

  /* Subheadings inside sections */
  .terms-subheading {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin: 20px 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .terms-subheading { color: #0F172A; }

  /* Visual Enhancements for Key Clauses */
  .terms-callout {
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

  /* Product Showcase Pills */
  .product-tag-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 14px 0 16px;
  }
  .product-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--rx-text, #F5F7FF);
    font-weight: 500;
  }
  html.light-mode .product-tag {
    background: #F1F5F9;
    border-color: #E2E8F0;
    color: #1E293B;
  }
  .product-tag i { color: var(--rx-primary, #16B8FF); }

  /* Payment Badges Grid */
  .payment-pill-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 14px 0;
  }
  .payment-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--rx-border, #222A3D);
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--rx-text, #F5F7FF);
  }
  html.light-mode .payment-pill {
    background: #F8FAFC;
    border-color: #E2E8F0;
    color: #1E293B;
  }

  /* Order Status Timeline Flow */
  .status-flow-container {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 12px;
    padding: 16px;
    margin: 16px 0;
  }
  html.light-mode .status-flow-container {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .status-flow-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--rx-text-muted, #6F7890);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
  }
  .status-chips-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }
  .status-chip {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .status-chip-pending { background: rgba(245, 158, 11, 0.15); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.3); }
  .status-chip-info { background: rgba(22, 184, 255, 0.15); color: #16B8FF; border: 1px solid rgba(22, 184, 255, 0.3); }
  .status-chip-processing { background: rgba(139, 92, 246, 0.15); color: #A78BFA; border: 1px solid rgba(139, 92, 246, 0.3); }
  .status-chip-success { background: rgba(34, 197, 94, 0.15); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.3); }
  .status-chip-danger { background: rgba(239, 68, 68, 0.15); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); }
  .status-chip-muted { background: rgba(255, 255, 255, 0.06); color: var(--rx-text-secondary, #A5AEC2); border: 1px solid var(--rx-border, #222A3D); }
  html.light-mode .status-chip-muted { background: #EDF2F7; color: #475569; border-color: #CBD5E1; }

  /* Refund Comparison Two-Column Box */
  .refund-box-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin: 18px 0;
  }
  .refund-box {
    border-radius: 12px;
    padding: 18px;
  }
  .refund-box-eligible {
    background: rgba(34, 197, 94, 0.05);
    border: 1px solid rgba(34, 197, 94, 0.25);
  }
  .refund-box-ineligible {
    background: rgba(239, 68, 68, 0.05);
    border: 1px solid rgba(239, 68, 68, 0.25);
  }
  .refund-box-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  .refund-box-eligible .refund-box-title { color: #22C55E; }
  .refund-box-ineligible .refund-box-title { color: #EF4444; }

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
  .terms-toast {
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
  .terms-toast.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
  }

  /* Responsive Adjustments */
  @media (max-width: 991.98px) {
    .terms-sidebar {
      position: static;
      max-height: none;
      margin-bottom: 2rem;
    }
    .terms-toc-list {
      max-height: 280px;
    }
    .terms-section-card {
      padding: 22px 20px;
    }
  }

  /* Print Stylesheet */
  @media print {
    body { background: #ffffff !important; color: #000000 !important; font-size: 11pt !important; }
    .rx-navbar, .rx-footer, .terms-hero-actions, .terms-sidebar, #btnBackToTop, #termsProgressBar, .btn-copy-anchor {
      display: none !important;
    }
    .terms-section-card {
      border: 1px solid #ddd !important;
      box-shadow: none !important;
      page-break-inside: avoid;
      margin-bottom: 20px !important;
      background: #ffffff !important;
      color: #000000 !important;
    }
    .terms-hero-wrap {
      background: none !important;
      border: none !important;
      padding: 1rem 0 !important;
    }
    .terms-hero-title span {
      -webkit-text-fill-color: #000000 !important;
      color: #000000 !important;
    }
  }
</style>

<!-- ============================================================
     HERO HEADER SECTION
     ============================================================ -->
<section class="terms-hero-wrap">
  <div class="container text-center">
    
    <!-- Badge -->
    <div>
      <span class="terms-badge-pill">
        <i class="bi bi-shield-check"></i> Official Agreement &amp; Policies
      </span>
    </div>

    <!-- Main Title -->
    <h1 class="terms-hero-title">
      Terms &amp; <span>Conditions</span>
    </h1>

    <!-- Subtitle / Lead -->
    <p class="terms-hero-lead">
      These Terms and Conditions govern your access to and use of TOPUPERS gaming top-up services, website, digital products, and customer support in Bangladesh.
    </p>

    <!-- Metadata Badges -->
    <div class="terms-meta-strip">
      <div class="terms-meta-item">
        <i class="bi bi-calendar-event"></i>
        <span>Effective Date: <strong>26 October 2026</strong></span>
      </div>
      <div class="terms-meta-item">
        <i class="bi bi-building"></i>
        <span>Business: <strong>TOPUPERS</strong></span>
      </div>
      <div class="terms-meta-item">
        <i class="bi bi-globe2"></i>
        <span>Website: <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" style="color:var(--rx-primary); text-decoration:none;"><strong>topupers.com</strong></a></span>
      </div>
      <div class="terms-meta-item">
        <i class="bi bi-geo-alt"></i>
        <span>Jurisdiction: <strong>Bangladesh</strong></span>
      </div>
      <div class="terms-meta-item">
        <span class="status-pulse-dot"></span>
        <span style="color:#22C55E; font-weight:600;">Active &amp; Binding</span>
      </div>
    </div>

    <!-- Quick Search & Actions Toolbar -->
    <div class="terms-hero-actions">
      <div class="terms-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="termsSearchInput" placeholder="Search terms (e.g., refund, game id, bKash)..." aria-label="Search terms and conditions">
      </div>
      <button type="button" class="terms-btn-action" onclick="window.print()" title="Print or save as PDF">
        <i class="bi bi-printer"></i>
        <span>Print Document</span>
      </button>
    </div>

  </div>
</section>

<!-- ============================================================
     MAIN BODY & TABLE OF CONTENTS
     ============================================================ -->
<div class="terms-container-wrap">
  <div class="container">

    <!-- Executive Summary / Core Highlights Cards -->
    <div class="terms-highlights-grid">
      <div class="terms-highlight-card">
        <div class="highlight-icon-wrap icon-teal">
          <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h4>Never Share Passwords or OTPs</h4>
        <p>TopUpers UID top-ups require only your Player ID and Server/Zone ID. We never ask for account passwords, OTPs, or recovery codes.</p>
      </div>

      <div class="terms-highlight-card">
        <div class="highlight-icon-wrap icon-green">
          <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <h4>Rapid Electronic Delivery</h4>
        <p>Routine game top-ups are fulfilled in minutes. Please double-check your game credentials before submitting payment.</p>
      </div>

      <div class="terms-highlight-card">
        <div class="highlight-icon-wrap icon-purple">
          <i class="bi bi-credit-card-2-front-fill"></i>
        </div>
        <h4>Verified Local Payments</h4>
        <p>Secure checkout via bKash, Nagad, Rocket, Upay, Bank Transfer, and Binance Pay with strict payment verification.</p>
      </div>

      <div class="terms-highlight-card">
        <div class="highlight-icon-wrap icon-amber">
          <i class="bi bi-arrow-counterclockwise"></i>
        </div>
        <h4>Fair Refund &amp; Remedy Guarantee</h4>
        <p>If an order cannot be fulfilled due to stock, technical, or store issues, you are entitled to a full prompt refund or alternative.</p>
      </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4">

      <!-- ==========================================
           LEFT COLUMN: Sticky Table of Contents
           ========================================== -->
      <div class="col-lg-4 col-xl-3">
        <nav class="terms-sidebar" id="termsStickyNav" aria-label="Terms of service navigation">
          
          <div class="terms-sidebar-header">
            <h3 class="terms-sidebar-title">
              <i class="bi bi-list-nested text-primary"></i>
              <span>Navigation</span>
            </h3>
            <span class="terms-count-badge">23 Sections</span>
          </div>

          <ul class="terms-toc-list" id="termsTocList">
            <li><a href="#section-1" class="terms-toc-link"><span class="toc-index">01</span><span>Introduction &amp; Acceptance</span></a></li>
            <li><a href="#section-2" class="terms-toc-link"><span class="toc-index">02</span><span>Products &amp; Services</span></a></li>
            <li><a href="#section-3" class="terms-toc-link"><span class="toc-index">03</span><span>Eligibility &amp; Responsibility</span></a></li>
            <li><a href="#section-4" class="terms-toc-link"><span class="toc-index">04</span><span>Game ID &amp; Account Info</span></a></li>
            <li><a href="#section-5" class="terms-toc-link"><span class="toc-index">05</span><span>Pricing, Offers &amp; Availability</span></a></li>
            <li><a href="#section-6" class="terms-toc-link"><span class="toc-index">06</span><span>Payments &amp; Verification</span></a></li>
            <li><a href="#section-7" class="terms-toc-link"><span class="toc-index">07</span><span>Order Confirmation &amp; Processing</span></a></li>
            <li><a href="#section-8" class="terms-toc-link"><span class="toc-index">08</span><span>Delivery Policy</span></a></li>
            <li><a href="#section-9" class="terms-toc-link"><span class="toc-index">09</span><span>Cancellation Policy</span></a></li>
            <li><a href="#section-10" class="terms-toc-link"><span class="toc-index">10</span><span>Refund &amp; Failed Orders</span></a></li>
            <li><a href="#section-11" class="terms-toc-link"><span class="toc-index">11</span><span>Digital Codes &amp; Subscriptions</span></a></li>
            <li><a href="#section-12" class="terms-toc-link"><span class="toc-index">12</span><span>Pre-orders &amp; Limited Items</span></a></li>
            <li><a href="#section-13" class="terms-toc-link"><span class="toc-index">13</span><span>Promotions &amp; Giveaways</span></a></li>
            <li><a href="#section-14" class="terms-toc-link"><span class="toc-index">14</span><span>Fraud Prevention &amp; Security</span></a></li>
            <li><a href="#section-15" class="terms-toc-link"><span class="toc-index">15</span><span>Privacy &amp; Personal Data</span></a></li>
            <li><a href="#section-16" class="terms-toc-link"><span class="toc-index">16</span><span>Third-Party Platforms</span></a></li>
            <li><a href="#section-17" class="terms-toc-link"><span class="toc-index">17</span><span>Intellectual Property</span></a></li>
            <li><a href="#section-18" class="terms-toc-link"><span class="toc-index">18</span><span>Acceptable Website Use</span></a></li>
            <li><a href="#section-19" class="terms-toc-link"><span class="toc-index">19</span><span>Limitation of Liability</span></a></li>
            <li><a href="#section-20" class="terms-toc-link"><span class="toc-index">20</span><span>Complaints &amp; Disputes</span></a></li>
            <li><a href="#section-21" class="terms-toc-link"><span class="toc-index">21</span><span>Changes to These Terms</span></a></li>
            <li><a href="#section-22" class="terms-toc-link"><span class="toc-index">22</span><span>Governing Law</span></a></li>
            <li><a href="#section-23" class="terms-toc-link"><span class="toc-index">23</span><span>Contact &amp; Customer Support</span></a></li>
          </ul>

          <div class="sidebar-help-box">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="bi bi-headset text-primary"></i>
              <strong style="color:var(--rx-text);">Need Assistance?</strong>
            </div>
            <p class="mb-2" style="font-size:0.78rem;">Questions regarding our terms or orders? Contact our team anytime.</p>
            <a href="<?= APP_URL ?>/contact.php" class="btn btn-sm btn-outline-primary w-100" style="border-radius:6px; font-size:0.78rem;">Support Center</a>
          </div>

        </nav>
      </div>

      <!-- ==========================================
           RIGHT COLUMN: 23 Detailed Terms Sections
           ========================================== -->
      <div class="col-lg-8 col-xl-9">

        <!-- Section 1 -->
        <article class="terms-section-card" id="section-1">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">01</div>
              <h2 class="section-card-title">Introduction and Acceptance</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-1')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Welcome to <strong>TOPUPERS</strong>, a gaming top-up and digital products store serving customers in Bangladesh.</p>
            <p>These Terms &amp; Conditions govern your access to and use of our website, social media pages, ordering services, customer support, and digital products and services offered by TOPUPERS.</p>
            <p>By accessing our services, placing an order, or using our website, you acknowledge that you have read and understood these Terms &amp; Conditions. Where required, we will request your explicit acceptance before completing an order.</p>
            <div class="terms-callout callout-warning">
              <i class="bi bi-exclamation-octagon-fill callout-icon"></i>
              <div>
                <strong>Notice:</strong> If you do not agree with these Terms, please do not use our services.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 2 -->
        <article class="terms-section-card" id="section-2">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">02</div>
              <h2 class="section-card-title">Our Products and Services</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-2')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>TOPUPERS may offer the following products and services, subject to availability:</p>

            <div class="product-tag-wrap">
              <span class="product-tag"><i class="bi bi-controller"></i> Mobile Legends: Bang Bang (MLBB) Diamonds</span>
              <span class="product-tag"><i class="bi bi-calendar-check"></i> MLBB Weekly Passes &amp; Bundles</span>
              <span class="product-tag"><i class="bi bi-fire"></i> Free Fire Diamonds</span>
              <span class="product-tag"><i class="bi bi-shield-shaded"></i> PUBG Mobile UC</span>
              <span class="product-tag"><i class="bi bi-gift"></i> Gift Cards &amp; Redeem Codes</span>
              <span class="product-tag"><i class="bi bi-stars"></i> Digital Currencies &amp; Tokens</span>
              <span class="product-tag"><i class="bi bi-gem"></i> Subscriptions &amp; Memberships</span>
              <span class="product-tag"><i class="bi bi-hourglass-split"></i> Pre-orders &amp; Limited Items</span>
            </div>

            <ul class="terms-list">
              <li><strong>Mobile Legends: Bang Bang (MLBB):</strong> Diamonds, Weekly Passes, and other eligible in-game products.</li>
              <li><strong>Free Fire:</strong> Diamonds and other eligible game products.</li>
              <li><strong>PUBG Mobile:</strong> UC and other supported in-game products.</li>
              <li><strong>Other supported titles:</strong> Games, digital currencies, tokens, subscriptions, gift cards, redeem codes, and digital products.</li>
              <li><strong>Pre-order &amp; Special items:</strong> Pre-order and limited-availability products.</li>
            </ul>

            <p>Products, denominations, regions, prices, and delivery methods may change from time to time.</p>
            <p>Some products may be fulfilled through authorized or third-party suppliers, reseller platforms, APIs, or manual processing. TOPUPERS will not knowingly offer products that it is not authorized to resell where authorization is required.</p>
            
            <div class="terms-callout callout-info">
              <i class="bi bi-info-circle-fill callout-icon"></i>
              <div>
                <strong>Independent Reseller Disclosure:</strong> TOPUPERS is an independent reseller and is not affiliated with, endorsed by, or officially sponsored by any game publisher unless explicitly stated.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 3 -->
        <article class="terms-section-card" id="section-3">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">03</div>
              <h2 class="section-card-title">Eligibility and Customer Responsibility</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-3')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>By placing an order with TOPUPERS, you confirm that:</p>
            <ul class="terms-list">
              <li>You are legally capable of entering into the transaction or have appropriate permission from a parent or legal guardian.</li>
              <li>The information you provide is accurate and complete.</li>
              <li>Your payment method is lawfully available for your use.</li>
              <li>You will not use TOPUPERS for fraud, unauthorized transactions, or unlawful activities.</li>
            </ul>
            <p>Customers are responsible for complying with the applicable game rules, platform policies, and relevant laws.</p>
          </div>
        </article>

        <!-- Section 4 -->
        <article class="terms-section-card" id="section-4">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">04</div>
              <h2 class="section-card-title">Game ID, Server ID, Region and Account Information</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-4')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Customers must carefully verify all information before submitting an order, including where applicable:</p>
            <ul class="terms-list">
              <li><strong>Game ID or Player ID</strong></li>
              <li><strong>Server ID, Zone ID, or region</strong></li>
              <li><strong>In-game name (IGN)</strong></li>
              <li><strong>Product, denomination, subscription duration, or selected package</strong></li>
            </ul>
            <p>TOPUPERS is not responsible for a failed or incorrectly delivered order caused by inaccurate information supplied by the customer, provided the information was reasonably checked and the applicable risk was clearly disclosed.</p>
            <p>If an order has not yet been processed, contact support immediately to request a correction or cancellation. Changes cannot be guaranteed after processing begins.</p>
            
            <div class="terms-callout callout-warning">
              <i class="bi bi-shield-slash-fill callout-icon"></i>
              <div>
                <strong>Critical Security Rule:</strong> Never share your game password, OTP, recovery code, or other sensitive login credentials with TOPUPERS. UID-based top-ups should use the required player information only.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 5 -->
        <article class="terms-section-card" id="section-5">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">05</div>
              <h2 class="section-card-title">Pricing, Offers and Availability</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-5')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>All prices displayed for Bangladesh customers will ordinarily be shown in <strong>Bangladeshi Taka (BDT)</strong>, unless a product or payment method clearly states otherwise.</p>
            <p>Before confirming payment, customers should review the selected product, quantity, applicable discounts, and final payable amount.</p>
            <p>Prices and promotions may change because of supplier rates, exchange rates, stock availability, or promotional campaigns. However, a confirmed order will ordinarily be processed at its accepted price unless the customer agrees to a correction or applicable law requires otherwise.</p>
            <p>Coupons and promotional offers are subject to their stated eligibility, expiry, and usage conditions. Offers cannot be combined unless expressly permitted.</p>
            <p>If a genuine pricing error or product availability issue affects an order, TOPUPERS will contact the customer and offer an appropriate resolution, including cancellation and a refund where applicable.</p>
          </div>
        </article>

        <!-- Section 6 -->
        <article class="terms-section-card" id="section-6">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">06</div>
              <h2 class="section-card-title">Payments and Payment Verification</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-6')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>TOPUPERS may support payment methods such as:</p>
            <div class="payment-pill-grid">
              <span class="payment-pill"><i class="bi bi-phone text-primary"></i> bKash</span>
              <span class="payment-pill"><i class="bi bi-phone text-warning"></i> Nagad</span>
              <span class="payment-pill"><i class="bi bi-phone text-danger"></i> Rocket</span>
              <span class="payment-pill"><i class="bi bi-wallet2 text-info"></i> Upay</span>
              <span class="payment-pill"><i class="bi bi-bank text-success"></i> Bank Transfer</span>
              <span class="payment-pill"><i class="bi bi-currency-bitcoin text-warning"></i> Binance Pay</span>
            </div>
            <p>Available payment methods may vary by product and over time.</p>
            <p>Customers must:</p>
            <ul class="terms-list">
              <li>Pay the amount shown for the selected order.</li>
              <li>Use the correct payment instructions and recipient details.</li>
              <li>Provide a valid transaction reference or payment identifier when requested.</li>
              <li>Avoid submitting false payment screenshots, forged transaction IDs, or altered receipts.</li>
            </ul>
            <p>A submitted screenshot or transaction ID does not, by itself, establish successful payment. An order will be treated as paid only after the payment has been reasonably verified through the applicable payment process.</p>
            <p>Orders may remain pending while payment is verified. Customers should not send repeated payments unless support confirms that another payment is necessary.</p>
            <div class="terms-callout callout-warning">
              <i class="bi bi-shield-lock-fill callout-icon"></i>
              <div>
                <strong>Zero PIN Policy:</strong> TOPUPERS will never request a customer's payment PIN or OTP.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 7 -->
        <article class="terms-section-card" id="section-7">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">07</div>
              <h2 class="section-card-title">Order Confirmation and Processing</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-7')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>After placing an order, customers should retain the order number and payment reference for future communication.</p>
            
            <div class="status-flow-container">
              <div class="status-flow-title">Order Lifecycle &amp; Status Stages</div>
              <div class="status-chips-wrap">
                <span class="status-chip status-chip-pending"><i class="bi bi-clock"></i> Pending Payment</span>
                <i class="bi bi-arrow-right text-muted"></i>
                <span class="status-chip status-chip-info"><i class="bi bi-search"></i> Payment Verification</span>
                <i class="bi bi-arrow-right text-muted"></i>
                <span class="status-chip status-chip-success"><i class="bi bi-check-circle"></i> Paid</span>
                <i class="bi bi-arrow-right text-muted"></i>
                <span class="status-chip status-chip-processing"><i class="bi bi-gear-wide-connected"></i> Processing</span>
                <i class="bi bi-arrow-right text-muted"></i>
                <span class="status-chip status-chip-success"><i class="bi bi-patch-check-fill"></i> Completed</span>
              </div>
              <div class="status-chips-wrap mt-2">
                <span class="status-chip status-chip-danger"><i class="bi bi-x-circle"></i> Failed</span>
                <span class="status-chip status-chip-muted"><i class="bi bi-slash-circle"></i> Cancelled</span>
                <span class="status-chip status-chip-pending"><i class="bi bi-arrow-repeat"></i> Refund Pending</span>
                <span class="status-chip status-chip-success"><i class="bi bi-check2-all"></i> Refunded</span>
              </div>
            </div>

            <p>An order confirmation acknowledges receipt of the order; it does not necessarily mean that the digital product has been delivered.</p>
            <p>TOPUPERS may decline, suspend, or cancel an order where payment cannot be verified, the product is unavailable, a supplier rejects the transaction, or a reasonable fraud or security concern arises. Any amount received for an order that cannot be fulfilled will be handled under the refund policy below.</p>
          </div>
        </article>

        <!-- Section 8 -->
        <article class="terms-section-card" id="section-8">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">08</div>
              <h2 class="section-card-title">Delivery Policy</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-8')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Digital products are generally delivered electronically through a supported supplier, game system, redeem-code delivery method, or another method stated for the product.</p>
            <p>Many routine top-ups may be delivered within minutes. However, delivery time is not guaranteed unless a specific delivery commitment is expressly stated for that product.</p>
            
            <h4 class="terms-subheading"><i class="bi bi-exclamation-circle text-warning"></i> Delivery May Take Longer Due To:</h4>
            <ul class="terms-list">
              <li>Supplier or API delays.</li>
              <li>Game server maintenance or outages.</li>
              <li>Payment verification requirements.</li>
              <li>Region or product restrictions.</li>
              <li>High order volumes or manual processing queues.</li>
              <li>Pre-order schedules or third-party technical issues.</li>
            </ul>

            <p>Where an estimated delivery time or pre-order deadline is displayed, customers should use that product-specific information when placing an order.</p>
            <p>If delivery is delayed beyond the communicated timeframe, contact TOPUPERS support with your order number. We will investigate and provide an update or an appropriate resolution.</p>
            
            <div class="terms-callout callout-success">
              <i class="bi bi-patch-check-fill callout-icon"></i>
              <div>
                <strong>Honest Fulfillment Commitment:</strong> TOPUPERS will not falsely mark an order as completed when delivery has not been confirmed.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 9 -->
        <article class="terms-section-card" id="section-9">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">09</div>
              <h2 class="section-card-title">Cancellation Policy</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-9')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Customers may request cancellation before processing begins.</p>
            <p>Once an order has entered processing, cancellation may no longer be possible because digital products can be fulfilled immediately or become non-reversible through the supplier.</p>
            <p>Cancellation requests are assessed according to the order status, product type, supplier rules, and applicable law.</p>
            <p>If TOPUPERS cancels a paid order because it cannot supply the product, the customer will be offered an appropriate resolution, including a refund where applicable.</p>
          </div>
        </article>

        <!-- Section 10 -->
        <article class="terms-section-card" id="section-10">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">10</div>
              <h2 class="section-card-title">Refund and Failed Order Policy</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-10')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Digital products may be irreversible once successfully delivered or redeemed. However, this does not remove any refund or remedy rights available under applicable law.</p>

            <div class="refund-box-grid">
              <!-- Eligible Box -->
              <div class="refund-box refund-box-eligible">
                <div class="refund-box-title">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Eligible Situations for Refund</span>
                </div>
                <ul class="terms-list mb-0">
                  <li>Payment is confirmed but TOPUPERS cannot deliver the purchased product.</li>
                  <li>A product is unavailable after payment and an alternative is not accepted.</li>
                  <li>The transaction fails and the product has not been delivered.</li>
                  <li>A duplicate payment is verified.</li>
                  <li>TOPUPERS confirms an incorrect charge or another fulfillment error attributable to the store.</li>
                  <li>A refund is otherwise required by applicable law.</li>
                </ul>
              </div>

              <!-- Non-refundable Box -->
              <div class="refund-box refund-box-ineligible">
                <div class="refund-box-title">
                  <i class="bi bi-x-circle-fill"></i>
                  <span>Generally Non-Refundable Situations</span>
                </div>
                <ul class="terms-list mb-0">
                  <li>The correct product has been successfully delivered to the supplied game account.</li>
                  <li>A valid digital code has been disclosed, delivered, or redeemed, except where a remedy is required by law.</li>
                  <li>The customer supplied an incorrect Game ID, Server ID, region, or other required information and the product was successfully delivered as a result.</li>
                  <li>The customer changes their mind after successful delivery of an irreversible digital product.</li>
                </ul>
              </div>
            </div>

            <p style="font-size:0.92rem; font-style:italic; color:var(--rx-text-muted);">* These exclusions do not apply where TOPUPERS is responsible for the failure or where applicable law requires a refund or other remedy.</p>

            <h4 class="terms-subheading"><i class="bi bi-clipboard2-check text-primary"></i> Refund Procedure</h4>
            <ul class="terms-list">
              <li><strong>Step 1 — Contact Support:</strong> Customers should contact support with their order number, payment reference, and a clear explanation of the problem.</li>
              <li><strong>Step 2 — Investigation:</strong> TOPUPERS will investigate using available order records and supplier information.</li>
              <li><strong>Step 3 — Settlement:</strong> If a refund is approved, it will ordinarily be returned through the original payment method where feasible and lawful, or through another mutually agreed lawful method.</li>
            </ul>
            <p>The expected processing time and any relevant conditions will be communicated to the customer. Third-party payment providers may require additional processing time.</p>
            
            <div class="terms-callout callout-info">
              <i class="bi bi-shield-check callout-icon"></i>
              <div>
                <strong>Consumer Rights Assurance:</strong> TOPUPERS will not impose a blanket no-refund rule that overrides applicable consumer rights.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 11 -->
        <article class="terms-section-card" id="section-11">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">11</div>
              <h2 class="section-card-title">Digital Codes, Gift Cards and Subscriptions</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-11')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Customers must verify the product, region, denomination, platform compatibility, and any redemption restrictions before purchasing a digital code, gift card, subscription, or similar product.</p>
            <p>Unless otherwise stated:</p>
            <ul class="terms-list">
              <li>Codes are subject to the issuing platform's validity and redemption rules.</li>
              <li>Region-locked products may not work on accounts registered in another region.</li>
              <li>Subscriptions may be subject to renewal, expiry, device, account, or eligibility restrictions imposed by the relevant provider.</li>
            </ul>
            <p>Delivered or redeemed codes and successfully activated subscriptions are generally irreversible.</p>
            <p>If a code is invalid, defective, or unusable due to an issue attributable to TOPUPERS or its supplier, customers should report the issue promptly and provide reasonable evidence, <strong>preferably within 48 hours of delivery</strong>. This reporting period helps us investigate quickly and does not waive any mandatory legal rights.</p>
            <p>TOPUPERS does not guarantee that a third-party service will remain available, unchanged, or compatible indefinitely.</p>
          </div>
        </article>

        <!-- Section 12 -->
        <article class="terms-section-card" id="section-12">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">12</div>
              <h2 class="section-card-title">Pre-orders and Limited-Availability Products</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-12')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Pre-orders are subject to the product-specific estimated release or delivery timeframe displayed at the time of purchase.</p>
            <p>A pre-order may be delayed because of publisher schedules, supplier availability, regional restrictions, or other external factors.</p>
            <p>If a pre-order cannot be fulfilled, TOPUPERS will notify the customer and offer an appropriate resolution, including a refund where applicable.</p>
            <p>Customers should not assume that an announced game release date guarantees immediate availability through TOPUPERS.</p>
          </div>
        </article>

        <!-- Section 13 -->
        <article class="terms-section-card" id="section-13">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">13</div>
              <h2 class="section-card-title">Promotions, Giveaways and Discounts</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-13')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Promotions, giveaways, bonus products, and discounts are subject to their published rules.</p>
            <p>TOPUPERS may disqualify entries involving fake accounts, payment manipulation, impersonation, or other fraudulent conduct.</p>
            <p>If a promotion is changed or cancelled, TOPUPERS will communicate the change where reasonably practicable and will respect any obligations already incurred under applicable law.</p>
          </div>
        </article>

        <!-- Section 14 -->
        <article class="terms-section-card" id="section-14">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">14</div>
              <h2 class="section-card-title">Fraud Prevention and Account Security</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-14')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>TOPUPERS may review suspicious transactions and request reasonable information necessary to verify an order.</p>
            <p>We may delay or refuse a transaction if there is a reasonable concern involving stolen payment methods, fake payment evidence, unauthorized account use, abuse of promotional offers, or other fraudulent activity.</p>
            <p>Customers must not attempt to exploit website errors, interfere with APIs, bypass security measures, or gain unauthorized access to systems or accounts.</p>
            <p>Any personal information collected for verification must be handled in accordance with our Privacy Policy and applicable law.</p>
          </div>
        </article>

        <!-- Section 15 -->
        <article class="terms-section-card" id="section-15">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">15</div>
              <h2 class="section-card-title">Privacy and Personal Information</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-15')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>TOPUPERS may collect information necessary to process orders, verify payments, deliver products, prevent fraud, and provide customer support.</p>
            <p>Depending on the transaction, this may include contact details, order records, payment references, game identifiers, and support communications.</p>
            <p>We will explain the purposes of collection and the applicable handling of personal information in our <a href="<?= APP_URL ?>/privacy.php" style="color:var(--rx-primary); font-weight:600;">Privacy Policy</a>. Information will not be collected or used beyond the stated purposes without an appropriate legal basis or required consent.</p>
            <p>We may share limited transaction information with suppliers, payment providers, or service providers where necessary to complete an order, verify payment, prevent fraud, or comply with law.</p>
            <p>Customers should not submit passwords, OTPs, or unrelated sensitive information through support channels.</p>
          </div>
        </article>

        <!-- Section 16 -->
        <article class="terms-section-card" id="section-16">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">16</div>
              <h2 class="section-card-title">Third-Party Platforms and Services</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-16')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Some products depend on third-party game publishers, suppliers, payment processors, or digital platforms.</p>
            <p>TOPUPERS does not control third-party outages, maintenance schedules, product eligibility rules, or changes to external services.</p>
            <p>Where a third-party issue prevents an order from being completed, we will investigate and offer an appropriate resolution under these Terms and applicable law.</p>
            <p>Third-party limitations do not excuse TOPUPERS from handling customer payments, complaints, or failed orders fairly.</p>
          </div>
        </article>

        <!-- Section 17 -->
        <article class="terms-section-card" id="section-17">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">17</div>
              <h2 class="section-card-title">Intellectual Property and Brand Use</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-17')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Game titles, logos, artwork, trademarks, and other intellectual property belong to their respective owners.</p>
            <p>TOPUPERS does not claim ownership of third-party intellectual property. Product images and descriptions are provided for identification and informational purposes, subject to applicable rights and permissions.</p>
            <p>The TOPUPERS name, logo, original website content, and original promotional materials may not be copied or used commercially without permission, except where permitted by law.</p>
          </div>
        </article>

        <!-- Section 18 -->
        <article class="terms-section-card" id="section-18">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">18</div>
              <h2 class="section-card-title">Website Availability and Acceptable Use</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-18')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>We aim to keep our website and services available and secure, but uninterrupted access cannot be guaranteed.</p>
            <p>Customers must not:</p>
            <ul class="terms-list">
              <li>Attempt unauthorized access to the website, database, or administrative systems.</li>
              <li>Introduce malicious software or interfere with website operations.</li>
              <li>Submit false orders or fabricated payment evidence.</li>
              <li>Abuse support channels or attempt to disrupt services.</li>
              <li>Use the website for unlawful purposes.</li>
            </ul>
            <p>TOPUPERS may temporarily suspend affected services for maintenance, security, or technical reasons.</p>
          </div>
        </article>

        <!-- Section 19 -->
        <article class="terms-section-card" id="section-19">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">19</div>
              <h2 class="section-card-title">Limitation of Liability</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-19')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>To the extent permitted by applicable law, TOPUPERS will not be liable for indirect losses that could not reasonably have been anticipated when an order was placed.</p>
            <p>Nothing in these Terms excludes liability for fraud, intentional misconduct, or any liability that cannot lawfully be excluded or limited.</p>
            <p>For a specific order, any remedy or limitation will be assessed in light of the order value, the nature of the issue, the parties' responsibilities, and applicable law.</p>
          </div>
        </article>

        <!-- Section 20 -->
        <article class="terms-section-card" id="section-20">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">20</div>
              <h2 class="section-card-title">Complaints and Dispute Resolution</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-20')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>Customers should first contact TOPUPERS support and provide their order number, payment reference, and relevant evidence.</p>
            
            <div class="terms-callout callout-info">
              <i class="bi bi-stopwatch-fill callout-icon"></i>
              <div>
                <strong>72-Hour Response Commitment:</strong> We aim to acknowledge and investigate complaints promptly and to provide a meaningful response or proposed resolution within <strong>72 hours</strong> where practicable, subject to the nature of the complaint and any applicable legal deadlines.
              </div>
            </div>

            <p>If a dispute cannot be resolved directly, either party may pursue remedies available under the applicable laws of Bangladesh.</p>
            <p>Nothing in these Terms prevents a customer from approaching a competent consumer protection authority or other lawful dispute-resolution body.</p>
          </div>
        </article>

        <!-- Section 21 -->
        <article class="terms-section-card" id="section-21">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">21</div>
              <h2 class="section-card-title">Changes to These Terms</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-21')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>TOPUPERS may update these Terms to reflect changes in products, payment methods, security practices, operational requirements, or applicable law.</p>
            <p>The revised version will be published with an updated effective date. Changes will not retroactively remove rights that have already accrued to customers or override mandatory legal protections.</p>
            <p>Customers should review the current Terms before placing an order.</p>
          </div>
        </article>

        <!-- Section 22 -->
        <article class="terms-section-card" id="section-22">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">22</div>
              <h2 class="section-card-title">Governing Law</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-22')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>These Terms are intended to operate in accordance with the <strong>applicable laws of Bangladesh</strong>, including relevant consumer protection and digital commerce requirements.</p>
            <p>Any dispute will be handled by a competent authority or court with jurisdiction under applicable law.</p>
          </div>
        </article>

        <!-- Section 23 -->
        <article class="terms-section-card" id="section-23">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">23</div>
              <h2 class="section-card-title">Contact and Customer Support</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-23')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="terms-card-body">
            <p>For order-related questions, payment verification, delivery delays, cancellation requests, or complaints, please contact TOPUPERS through the official support channel displayed on our website or official Facebook page.</p>
            <p>When contacting support, include your <strong>order number</strong> and <strong>relevant transaction reference</strong>. Do not share your payment PIN, OTP, or game account password.</p>
            
            <div class="p-3 rounded-3 mt-3 mb-2" style="background:var(--rx-surface-hover); border:1px solid var(--rx-border);">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                  <h4 style="font-size:1.05rem; font-weight:700; color:var(--rx-text); margin:0 0 4px;">TOPUPERS Support Desk</h4>
                  <p class="mb-0" style="font-size:0.88rem; color:var(--rx-text-secondary);">Gaming Top-Up &amp; Digital Store — Bangladesh</p>
                </div>
                <div class="d-flex gap-2">
                  <a href="<?= APP_URL ?>/contact.php" class="btn btn-primary btn-sm px-3" style="border-radius:8px;">
                    <i class="bi bi-chat-dots-fill me-1"></i> Contact Support
                  </a>
                  <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:8px;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Visit Website
                  </a>
                </div>
              </div>
            </div>

            <div class="text-center mt-4 pt-3 border-top" style="border-color:var(--rx-border-light) !important;">
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
<div id="termsToast" class="terms-toast">
  <i class="bi bi-check2-circle text-primary"></i>
  <span>Section link copied to clipboard!</span>
</div>

<!-- Interactive Client-side Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Reading Progress Bar
  const progressBar = document.getElementById('termsProgressBar');
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
  const sections = document.querySelectorAll('.terms-section-card');
  const tocLinks = document.querySelectorAll('.terms-toc-link');

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
        // keep visible in TOC scroll container
        const tocList = document.getElementById('termsTocList');
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
  const searchInput = document.getElementById('termsSearchInput');
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
    // fallback
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
  const toast = document.getElementById('termsToast');
  if (!toast) return;
  toast.querySelector('span').innerText = msg;
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 2400);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
