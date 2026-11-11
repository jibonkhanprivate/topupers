<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Return & Refund Policy — TopUpers';
$pageDescription = 'Official Return and Refund Policy for TopUpers gaming top-ups and digital products store in Bangladesh.';

include __DIR__ . '/includes/header.php';

$siteName = !empty(getSetting('site_name')) ? getSetting('site_name') : (defined('APP_NAME') ? APP_NAME : 'TopUpers');
$siteEmail = !empty(getSetting('site_email')) ? getSetting('site_email') : 'support@topupers.com';
?>

<!-- Reading Progress Bar -->
<div id="refundProgressBar" aria-hidden="true"></div>

<!-- Custom Scoped Styles for Modern Return & Refund Policy -->
<style>
  /* Reading Progress Bar */
  #refundProgressBar {
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
  .refund-hero-wrap {
    position: relative;
    background: radial-gradient(1000px circle at 50% -20%, rgba(22, 184, 255, 0.12), transparent 70%),
                linear-gradient(180deg, var(--rx-bg-secondary, #0A0E18) 0%, var(--rx-bg, #070A12) 100%);
    border-bottom: 1px solid var(--rx-border, #222A3D);
    padding: 3.5rem 0 3rem;
    overflow: hidden;
  }
  html.light-mode .refund-hero-wrap {
    background: radial-gradient(1000px circle at 50% -20%, rgba(7, 155, 232, 0.08), transparent 70%),
                linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
    border-bottom-color: var(--rx-border, #E1E5EE);
  }
  .refund-hero-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    opacity: 0.6;
  }
  html.light-mode .refund-hero-wrap::before {
    background-image: radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    opacity: 0.8;
  }

  .refund-badge-pill {
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
  html.light-mode .refund-badge-pill {
    background: rgba(7, 155, 232, 0.08);
    border-color: rgba(7, 155, 232, 0.2);
    color: #0284c7;
  }

  .refund-hero-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    letter-spacing: -0.5px;
    color: var(--rx-text, #F5F7FF);
    line-height: 1.15;
    margin-bottom: 1rem;
  }
  .refund-hero-title span {
    background: linear-gradient(135deg, var(--rx-primary, #16B8FF), #A78BFA);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .refund-hero-lead {
    font-size: 1.05rem;
    color: var(--rx-text-secondary, #A5AEC2);
    max-width: 740px;
    margin: 0 auto 2rem;
    line-height: 1.6;
  }

  /* Metadata Strip */
  .refund-meta-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 2rem;
  }
  .refund-meta-item {
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
  html.light-mode .refund-meta-item {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    color: #475569;
  }
  .refund-meta-item strong {
    color: var(--rx-text, #F5F7FF);
    font-weight: 600;
  }
  html.light-mode .refund-meta-item strong { color: #0F172A; }
  .refund-meta-item i {
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

  /* Actions Toolbar & Search */
  .refund-hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .refund-search-box {
    position: relative;
    width: 100%;
    max-width: 380px;
  }
  .refund-search-box input {
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
  html.light-mode .refund-search-box input {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #0F172A;
  }
  .refund-search-box input:focus {
    border-color: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 0 3px rgba(22, 184, 255, 0.15);
  }
  .refund-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rx-text-muted, #6F7890);
    font-size: 0.95rem;
    pointer-events: none;
  }
  .refund-btn-action {
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
  html.light-mode .refund-btn-action {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #1E293B;
  }
  .refund-btn-action:hover {
    background: var(--rx-surface-hover, #151C2D);
    border-color: var(--rx-primary, #16B8FF);
    color: var(--rx-primary, #16B8FF);
    transform: translateY(-1px);
  }

  /* Executive Summary Highlight Cards */
  .refund-highlights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 2.5rem;
  }
  .refund-highlight-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 14px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  html.light-mode .refund-highlight-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
  }
  .refund-highlight-card:hover {
    transform: translateY(-3px);
    border-color: rgba(22, 184, 255, 0.4);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }
  html.light-mode .refund-highlight-card:hover {
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

  .refund-highlight-card h4 {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .refund-highlight-card h4 { color: #0F172A; }
  .refund-highlight-card p {
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.55;
  }

  /* Layout */
  .refund-container-wrap {
    padding: 3rem 0 5rem;
  }

  /* Sticky Sidebar Navigation */
  .refund-sidebar {
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
  html.light-mode .refund-sidebar {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  }
  .refund-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--rx-border, #222A3D);
  }
  html.light-mode .refund-sidebar-header { border-bottom-color: #E2E8F0; }
  .refund-sidebar-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    font-family: 'Outfit', sans-serif;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  html.light-mode .refund-sidebar-title { color: #0F172A; }
  .refund-count-badge {
    background: rgba(22, 184, 255, 0.12);
    color: var(--rx-primary, #16B8FF);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .refund-toc-list {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    padding-right: 4px;
    flex: 1;
  }
  .refund-toc-list::-webkit-scrollbar { width: 4px; }
  .refund-toc-list::-webkit-scrollbar-thumb {
    background: var(--rx-border, #222A3D);
    border-radius: 4px;
  }
  .refund-toc-link {
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
  html.light-mode .refund-toc-link { color: #64748B; }
  .refund-toc-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--rx-primary, #16B8FF);
  }
  html.light-mode .refund-toc-link:hover {
    background: rgba(0, 0, 0, 0.04);
    color: var(--rx-primary, #079BE8);
  }
  .refund-toc-link.active {
    background: rgba(22, 184, 255, 0.12);
    color: var(--rx-primary, #16B8FF);
    font-weight: 600;
    border-left: 3px solid var(--rx-primary, #16B8FF);
  }
  html.light-mode .refund-toc-link.active {
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
  .refund-toc-link.active .toc-index {
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

  /* Main Section Cards */
  .refund-section-card {
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
  html.light-mode .refund-section-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  }
  .refund-section-card:hover {
    border-color: rgba(22, 184, 255, 0.35);
  }
  .refund-section-card.section-target {
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
  html.light-mode .section-card-header { border-bottom-color: #E2E8F0; }
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
  .refund-section-card:hover .btn-copy-anchor { opacity: 1; }
  .btn-copy-anchor:hover {
    color: var(--rx-primary, #16B8FF);
    background: rgba(22, 184, 255, 0.1);
  }

  /* Typography */
  .refund-card-body {
    font-size: 0.98rem;
    line-height: 1.78;
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .refund-card-body { color: #334155; }
  .refund-card-body p { margin-bottom: 14px; }
  .refund-card-body p:last-child { margin-bottom: 0; }

  /* Subheadings */
  .refund-subheading {
    font-size: 1.08rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin: 22px 0 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .refund-subheading { color: #0F172A; }

  /* Custom Lists */
  .refund-list {
    list-style: none;
    padding-left: 0;
    margin: 14px 0 16px;
  }
  .refund-list li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 10px;
    line-height: 1.65;
  }
  .refund-list li::before {
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

  /* Sub-Clause Cards */
  .refund-subcard {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 14px;
    transition: transform 0.2s ease, border-color 0.2s ease;
  }
  html.light-mode .refund-subcard {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .refund-subcard:hover {
    border-color: rgba(22, 184, 255, 0.3);
  }
  .refund-subcard-title {
    font-size: 0.96rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .refund-subcard-title { color: #0F172A; }

  /* Callouts */
  .refund-callout {
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

  /* Review Criteria Grid */
  .criteria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin: 16px 0;
  }
  .criteria-item {
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
  html.light-mode .criteria-item {
    background: #F8FAFC;
    border-color: #E2E8F0;
    color: #1E293B;
  }
  .criteria-item i {
    color: var(--rx-primary, #16B8FF);
    font-size: 1.15rem;
    flex-shrink: 0;
  }

  /* Procedure Checklist Box */
  .checklist-box {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 12px;
    padding: 18px 22px;
    margin: 16px 0;
  }
  html.light-mode .checklist-box {
    background: #F8FAFC;
    border-color: #E2E8F0;
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
  .refund-toast {
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
  .refund-toast.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
  }

  /* Responsive Adjustments */
  @media (max-width: 991.98px) {
    .refund-sidebar {
      position: static;
      max-height: none;
      margin-bottom: 2rem;
    }
    .refund-toc-list {
      max-height: 280px;
    }
    .refund-section-card {
      padding: 22px 20px;
    }
  }

  /* Print Stylesheet */
  @media print {
    body { background: #ffffff !important; color: #000000 !important; font-size: 11pt !important; }
    .rx-navbar, .rx-footer, .refund-hero-actions, .refund-sidebar, #btnBackToTop, #refundProgressBar, .btn-copy-anchor {
      display: none !important;
    }
    .refund-section-card {
      border: 1px solid #ddd !important;
      box-shadow: none !important;
      page-break-inside: avoid;
      margin-bottom: 20px !important;
      background: #ffffff !important;
      color: #000000 !important;
    }
    .refund-hero-wrap {
      background: none !important;
      border: none !important;
      padding: 1rem 0 !important;
    }
    .refund-hero-title span {
      -webkit-text-fill-color: #000000 !important;
      color: #000000 !important;
    }
  }
</style>

<!-- ============================================================
     HERO HEADER SECTION
     ============================================================ -->
<section class="refund-hero-wrap">
  <div class="container text-center">
    
    <!-- Badge -->
    <div>
      <span class="refund-badge-pill">
        <i class="bi bi-arrow-counterclockwise"></i> Fair Resolution &amp; Consumer Protection
      </span>
    </div>

    <!-- Main Title -->
    <h1 class="refund-hero-title">
      Return &amp; <span>Refund Policy</span>
    </h1>

    <!-- Subtitle / Lead -->
    <p class="refund-hero-lead">
      At TOPUPERS, we aim to provide a reliable, transparent, and fair experience for every customer purchasing gaming top-ups and digital products. Because many of our products are delivered electronically and may be irreversible once processed, this policy outlines our clear rules for failed orders, duplicate payments, and remedies.
    </p>

    <!-- Metadata Badges -->
    <div class="refund-meta-strip">
      <div class="refund-meta-item">
        <i class="bi bi-calendar-event"></i>
        <span>Effective Date: <strong>26 October 2026</strong></span>
      </div>
      <div class="refund-meta-item">
        <i class="bi bi-clock-history"></i>
        <span>Last Updated: <strong>26 October 2026</strong></span>
      </div>
      <div class="refund-meta-item">
        <i class="bi bi-building"></i>
        <span>Business: <strong>TOPUPERS</strong></span>
      </div>
      <div class="refund-meta-item">
        <i class="bi bi-globe2"></i>
        <span>Website: <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" style="color:var(--rx-primary); text-decoration:none;"><strong>topupers.com</strong></a></span>
      </div>
      <div class="refund-meta-item">
        <i class="bi bi-headset"></i>
        <span>Support: <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary); text-decoration:none;"><strong><?= e($siteEmail) ?></strong></a></span>
      </div>
      <div class="refund-meta-item">
        <span class="status-pulse-dot"></span>
        <span style="color:#22C55E; font-weight:600;">Active Policy</span>
      </div>
    </div>

    <!-- Quick Search & Actions Toolbar -->
    <div class="refund-hero-actions">
      <div class="refund-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="refundSearchInput" placeholder="Search refund terms (e.g., duplicate, failed, 72 hours)..." aria-label="Search return and refund policy">
      </div>
      <button type="button" class="refund-btn-action" onclick="window.print()" title="Print or save as PDF">
        <i class="bi bi-printer"></i>
        <span>Print Document</span>
      </button>
    </div>

  </div>
</section>

<!-- ============================================================
     MAIN BODY & TABLE OF CONTENTS
     ============================================================ -->
<div class="refund-container-wrap">
  <div class="container">

    <!-- Executive Summary / Core Highlights Cards -->
    <div class="refund-highlights-grid">
      <div class="refund-highlight-card">
        <div class="highlight-icon-wrap icon-teal">
          <i class="bi bi-shield-check"></i>
        </div>
        <h4>Guaranteed Delivery Protection</h4>
        <p>If your payment is verified but TOPUPERS cannot fulfill the purchased product due to stock or system issues, you are guaranteed a full refund or remedy.</p>
      </div>

      <div class="refund-highlight-card">
        <div class="highlight-icon-wrap icon-green">
          <i class="bi bi-arrow-repeat"></i>
        </div>
        <h4>Duplicate Payment Resolution</h4>
        <p>Accidentally made double payments for the same order? Once verified through our payment channels, extra payments are promptly refunded.</p>
      </div>

      <div class="refund-highlight-card">
        <div class="highlight-icon-wrap icon-amber">
          <i class="bi bi-person-x-fill"></i>
        </div>
        <h4>Accurate Game ID Required</h4>
        <p>Products successfully delivered to an incorrect Player ID or Server ID provided by the customer cannot be reversed. Please verify your details carefully.</p>
      </div>

      <div class="refund-highlight-card">
        <div class="highlight-icon-wrap icon-purple">
          <i class="bi bi-stopwatch-fill"></i>
        </div>
        <h4>72-Hour Review Commitment</h4>
        <p>Our dedicated support team aims to investigate and provide an initial response or resolution to refund requests within 72 hours.</p>
      </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4">

      <!-- ==========================================
           LEFT COLUMN: Sticky Table of Contents
           ========================================== -->
      <div class="col-lg-4 col-xl-3">
        <nav class="refund-sidebar" id="refundStickyNav" aria-label="Refund policy navigation">
          
          <div class="refund-sidebar-header">
            <h3 class="refund-sidebar-title">
              <i class="bi bi-list-nested text-primary"></i>
              <span>Navigation</span>
            </h3>
            <span class="refund-count-badge">16 Sections</span>
          </div>

          <ul class="refund-toc-list" id="refundTocList">
            <li><a href="#section-1" class="refund-toc-link"><span class="toc-index">01</span><span>Our Policy</span></a></li>
            <li><a href="#section-2" class="refund-toc-link"><span class="toc-index">02</span><span>Products Covered</span></a></li>
            <li><a href="#section-3" class="refund-toc-link"><span class="toc-index">03</span><span>Eligible Refund Situations</span></a></li>
            <li><a href="#section-4" class="refund-toc-link"><span class="toc-index">04</span><span>Non-Refundable Situations</span></a></li>
            <li><a href="#section-5" class="refund-toc-link"><span class="toc-index">05</span><span>Incorrect Game ID / Server</span></a></li>
            <li><a href="#section-6" class="refund-toc-link"><span class="toc-index">06</span><span>Order Cancellation</span></a></li>
            <li><a href="#section-7" class="refund-toc-link"><span class="toc-index">07</span><span>Refund Request Procedure</span></a></li>
            <li><a href="#section-8" class="refund-toc-link"><span class="toc-index">08</span><span>Refund Review &amp; Decision</span></a></li>
            <li><a href="#section-9" class="refund-toc-link"><span class="toc-index">09</span><span>Method &amp; Processing Time</span></a></li>
            <li><a href="#section-10" class="refund-toc-link"><span class="toc-index">10</span><span>Pending Verification</span></a></li>
            <li><a href="#section-11" class="refund-toc-link"><span class="toc-index">11</span><span>Redeem Codes &amp; Subscriptions</span></a></li>
            <li><a href="#section-12" class="refund-toc-link"><span class="toc-index">12</span><span>Promotions &amp; Discounts</span></a></li>
            <li><a href="#section-13" class="refund-toc-link"><span class="toc-index">13</span><span>Fraudulent Claims</span></a></li>
            <li><a href="#section-14" class="refund-toc-link"><span class="toc-index">14</span><span>Support &amp; Complaints</span></a></li>
            <li><a href="#section-15" class="refund-toc-link"><span class="toc-index">15</span><span>Policy Updates</span></a></li>
            <li><a href="#section-16" class="refund-toc-link"><span class="toc-index">16</span><span>Contact Information</span></a></li>
          </ul>

          <div class="sidebar-help-box">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="bi bi-headset text-primary"></i>
              <strong style="color:var(--rx-text);">Support Desk</strong>
            </div>
            <p class="mb-2" style="font-size:0.78rem;">Have an issue with your order or need a refund review? Contact us directly.</p>
            <a href="<?= APP_URL ?>/contact.php" class="btn btn-sm btn-outline-primary w-100" style="border-radius:6px; font-size:0.78rem;">Submit Support Ticket</a>
          </div>

        </nav>
      </div>

      <!-- ==========================================
           RIGHT COLUMN: Detailed Policy Sections
           ========================================== -->
      <div class="col-lg-8 col-xl-9">

        <!-- Section 1 -->
        <article class="refund-section-card" id="section-1">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">01</div>
              <h2 class="section-card-title">Our Policy</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-1')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>At <strong>TOPUPERS</strong>, we aim to provide a reliable, transparent, and fair experience for every customer purchasing gaming top-ups and digital products.</p>
            <p>Because many of our products are delivered electronically and may be irreversible once processed, returns are not handled in the same way as physical goods.</p>
            <p>However, we review failed transactions, incorrect charges, duplicate payments, and eligible refund requests fairly, in accordance with this policy and applicable laws of Bangladesh.</p>
            <div class="refund-callout callout-info mb-0">
              <i class="bi bi-shield-check callout-icon"></i>
              <div>
                <strong>Consumer Rights Assurance:</strong> Nothing in this policy limits any mandatory consumer rights available under applicable law.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 2 -->
        <article class="refund-section-card" id="section-2">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">02</div>
              <h2 class="section-card-title">Products Covered</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-2')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>This policy applies to products and services offered by TOPUPERS, including:</p>

            <div class="product-tag-wrap">
              <span class="product-tag"><i class="bi bi-controller"></i> MLBB Diamonds &amp; Passes</span>
              <span class="product-tag"><i class="bi bi-fire"></i> Free Fire Diamonds</span>
              <span class="product-tag"><i class="bi bi-shield-shaded"></i> PUBG Mobile UC</span>
              <span class="product-tag"><i class="bi bi-gift"></i> Redeem Codes &amp; Gift Cards</span>
              <span class="product-tag"><i class="bi bi-gem"></i> Digital Subscriptions</span>
              <span class="product-tag"><i class="bi bi-hourglass-split"></i> Pre-orders &amp; Promotional Items</span>
            </div>

            <ul class="refund-list">
              <li><strong>Mobile Legends: Bang Bang (MLBB):</strong> Diamonds, Weekly Passes, and other supported in-game products.</li>
              <li><strong>Free Fire:</strong> Diamonds and supported game products.</li>
              <li><strong>PUBG Mobile:</strong> UC and other eligible in-game currencies.</li>
              <li><strong>Redeem Codes:</strong> Gift cards, vouchers, and digital redemption keys.</li>
              <li><strong>Digital Subscriptions:</strong> Membership products and recurring game passes.</li>
              <li><strong>Pre-orders &amp; Special items:</strong> Promotional products and other digital services sold through our website or official ordering channels.</li>
            </ul>

            <p class="mb-0">Product-specific conditions may apply where clearly disclosed before purchase.</p>
          </div>
        </article>

        <!-- Section 3 -->
        <article class="refund-section-card" id="section-3">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">03</div>
              <h2 class="section-card-title">Eligible Refund Situations</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-3')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>A refund or another appropriate resolution may be available in the following situations:</p>

            <!-- 3.1 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span>3.1 Payment Completed but Product Not Delivered</span>
              </div>
              <p class="mb-0">If payment has been verified but TOPUPERS cannot deliver the purchased product, we will investigate the order. If the product cannot be supplied within a reasonable period, we will offer an appropriate resolution, including a refund where applicable.</p>
            </div>

            <!-- 3.2 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-x-octagon-fill text-warning"></i>
                <span>3.2 Failed Orders</span>
              </div>
              <p class="mb-0">If an order fails and the purchased product has not been delivered, the customer may request a refund of the amount paid. If the failure is temporary, we may offer the customer the option to wait for delivery or request cancellation where possible.</p>
            </div>

            <!-- 3.3 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-arrow-repeat text-info"></i>
                <span>3.3 Duplicate Payments</span>
              </div>
              <p class="mb-0">If a customer accidentally pays twice for the same order, the duplicate payment will be investigated. Once verified, the extra payment will be refunded or otherwise resolved appropriately.</p>
            </div>

            <!-- 3.4 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-calculator text-primary"></i>
                <span>3.4 Incorrect Amount Charged</span>
              </div>
              <p class="mb-0">If TOPUPERS confirms that a customer was charged more than the accepted order amount, the excess amount will be refunded or corrected as appropriate.</p>
            </div>

            <!-- 3.5 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-box-seam text-purple"></i>
                <span>3.5 Incorrect Product Supplied by TOPUPERS</span>
              </div>
              <p class="mb-0">If TOPUPERS delivers a product that materially differs from the customer's confirmed order due to an error attributable to us, we will investigate and offer an appropriate correction, replacement, or refund where applicable.</p>
            </div>

            <!-- 3.6 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-slash-circle text-danger"></i>
                <span>3.6 Unavailable Products and Cancelled Pre-orders</span>
              </div>
              <p class="mb-0">If a product becomes unavailable after payment and TOPUPERS cannot fulfil the order, the customer will be notified and offered an appropriate resolution, including a refund where applicable.</p>
            </div>

            <!-- 3.7 -->
            <div class="refund-subcard mb-0">
              <div class="refund-subcard-title">
                <i class="bi bi-patch-check-fill text-success"></i>
                <span>3.7 Other Legally Required Refunds</span>
              </div>
              <p class="mb-0">Refunds or other remedies will be provided whenever required by applicable law, even if a particular situation is not expressly listed above.</p>
            </div>

          </div>
        </article>

        <!-- Section 4 -->
        <article class="refund-section-card" id="section-4">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">04</div>
              <h2 class="section-card-title">Non-Refundable Situations</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-4')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>A refund may generally not be available in the following circumstances, subject to applicable law:</p>

            <!-- 4.1 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-check2-all text-muted"></i>
                <span>4.1 Successfully Delivered Products</span>
              </div>
              <p class="mb-0">Gaming top-ups and digital products that have been successfully delivered to the correct account are generally non-refundable because the transaction may be irreversible.</p>
            </div>

            <!-- 4.2 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-exclamation-triangle text-warning"></i>
                <span>4.2 Incorrect Customer Information</span>
              </div>
              <p>TOPUPERS may not be responsible for an unsuccessful or incorrectly delivered order resulting from inaccurate information supplied by the customer, such as:</p>
              <ul class="refund-list mb-2">
                <li>Incorrect Game ID or Player ID.</li>
                <li>Incorrect Server ID or region.</li>
                <li>Incorrect product or denomination selected.</li>
                <li>Incorrect account details supplied for a subscription or digital product.</li>
              </ul>
              <p class="mb-0" style="font-size:0.88rem; color:var(--rx-text-muted);">* This exclusion applies only where the error is attributable to the customer and does not remove any remedy required by applicable law.</p>
            </div>

            <!-- 4.3 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-arrow-left-right text-info"></i>
                <span>4.3 Change of Mind</span>
              </div>
              <p class="mb-0">A change of mind after successful delivery of an irreversible digital product generally does not qualify for a refund, except where required by law or expressly permitted by the applicable product terms.</p>
            </div>

            <!-- 4.4 -->
            <div class="refund-subcard">
              <div class="refund-subcard-title">
                <i class="bi bi-upc text-purple"></i>
                <span>4.4 Delivered or Redeemed Digital Codes</span>
              </div>
              <p>Digital codes, gift cards, and vouchers that have been revealed, redeemed, or successfully activated are generally non-refundable.</p>
              <p class="mb-0">However, if a code is invalid, defective, or unusable because of an issue attributable to TOPUPERS or its supplier, we will investigate and provide an appropriate remedy where applicable.</p>
            </div>

            <!-- 4.5 -->
            <div class="refund-subcard mb-0">
              <div class="refund-subcard-title">
                <i class="bi bi-person-slash text-danger"></i>
                <span>4.5 Customer Account Restrictions</span>
              </div>
              <p>TOPUPERS cannot guarantee a refund solely because a game publisher or third-party platform later restricts a customer's account, changes its policies, or removes access for reasons outside TOPUPERS' responsibility.</p>
              <p class="mb-0">If the issue results from an incorrect product supplied by TOPUPERS or another failure attributable to us, we will investigate the claim.</p>
            </div>

          </div>
        </article>

        <!-- Section 5 -->
        <article class="refund-section-card" id="section-5">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">05</div>
              <h2 class="section-card-title">Incorrect Game ID or Server ID</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-5')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>Customers must carefully check their Game ID, Server ID, region, and selected product before confirming an order.</p>
            <p>If an error is discovered, contact TOPUPERS support immediately.</p>
            <ul class="refund-list">
              <li><strong>Before Processing:</strong> If processing has not started, we will attempt to correct or cancel the order where possible.</li>
              <li><strong>After Delivery:</strong> If the product has already been delivered to the supplied account, reversal may not be possible. Refund eligibility will depend on the circumstances, the supplier's capabilities, responsibility for the error, and applicable law.</li>
            </ul>
            <div class="refund-callout callout-warning mb-0">
              <i class="bi bi-exclamation-diamond-fill callout-icon"></i>
              <div>
                <strong>Notice on Recovery:</strong> TOPUPERS does not guarantee recovery of products delivered to an incorrect account supplied by the customer.
              </div>
            </div>
          </div>
        </article>

        <!-- Section 6 -->
        <article class="refund-section-card" id="section-6">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">06</div>
              <h2 class="section-card-title">Order Cancellation</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-6')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>Customers may request cancellation before an order enters processing.</p>
            <p>Once a digital product has entered processing, cancellation may not be possible because supplier transactions can be irreversible.</p>
            <p>If TOPUPERS cannot fulfil a paid order, we will notify the customer and provide an appropriate resolution, including a refund where applicable.</p>
            <p class="mb-0">Customers should contact support as soon as possible if they wish to cancel an order.</p>
          </div>
        </article>

        <!-- Section 7 -->
        <article class="refund-section-card" id="section-7">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">07</div>
              <h2 class="section-card-title">Refund Request Procedure</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-7')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>To request a refund, contact our official support channel and provide the following information:</p>

            <div class="checklist-box">
              <h4 class="refund-subheading mt-0 mb-3"><i class="bi bi-clipboard2-check text-primary"></i> Required Submission Checklist:</h4>
              <ul class="refund-list mb-0">
                <li><strong>TOPUPERS order number</strong></li>
                <li><strong>Payment method used</strong> (bKash, Nagad, Rocket, Upay, Bank, Binance, etc.)</li>
                <li><strong>Transaction ID or payment reference</strong>, if available</li>
                <li><strong>Product and amount purchased</strong></li>
                <li><strong>A clear explanation of the problem</strong></li>
                <li><strong>Relevant evidence</strong>, if reasonably necessary (e.g. uncropped receipt screenshot)</li>
              </ul>
            </div>

            <div class="refund-callout callout-warning">
              <i class="bi bi-shield-lock-fill callout-icon"></i>
              <div>
                <strong>Security Reminder:</strong> Please do not share your payment PIN, OTP, password, or other authentication credentials.
              </div>
            </div>

            <p>TOPUPERS may request additional information reasonably necessary to verify the payment and investigate the order.</p>
            <p class="mb-0" style="font-size:0.92rem; color:var(--rx-text-muted);">* Submitting a refund request does not automatically mean that the request will be approved.</p>
          </div>
        </article>

        <!-- Section 8 -->
        <article class="refund-section-card" id="section-8">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">08</div>
              <h2 class="section-card-title">Refund Review and Decision</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-8')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>Each refund request will be reviewed based on:</p>

            <div class="criteria-grid">
              <div class="criteria-item"><i class="bi bi-search"></i> Payment verification results</div>
              <div class="criteria-item"><i class="bi bi-file-earmark-text"></i> Order &amp; delivery records</div>
              <div class="criteria-item"><i class="bi bi-hdd-network"></i> Supplier transaction status</div>
              <div class="criteria-item"><i class="bi bi-person-lines-fill"></i> Information supplied by customer</div>
              <div class="criteria-item"><i class="bi bi-bug"></i> Cause of failure</div>
              <div class="criteria-item"><i class="bi bi-journal-bookmark"></i> Product terms &amp; legal rules</div>
            </div>

            <div class="refund-callout callout-info">
              <i class="bi bi-stopwatch-fill callout-icon"></i>
              <div>
                <strong>72-Hour Response Target:</strong> We aim to provide an initial response within <strong>72 hours</strong> where practicable. More complex cases may require additional investigation with third-party providers.
              </div>
            </div>

            <p class="mb-0">If a request is declined, we will provide an explanation where reasonably possible.</p>
          </div>
        </article>

        <!-- Section 9 -->
        <article class="refund-section-card" id="section-9">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">09</div>
              <h2 class="section-card-title">Refund Method and Processing Time</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-9')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>Approved refunds will ordinarily be returned through the <strong>original payment method</strong> where feasible and lawful.</p>
            <p>If the original payment method cannot reasonably be used, an alternative lawful method may be agreed with the customer.</p>
            <p>The time required to receive a refund may depend on the payment provider, bank, mobile financial service (bKash/Nagad/Rocket), or other processing institution.</p>
            <p>TOPUPERS will communicate the expected processing time once the refund method and relevant provider requirements are known.</p>
            <p class="mb-0">A refund will not be described as completed until the applicable refund process has been completed or confirmation is available.</p>
          </div>
        </article>

        <!-- Section 10 -->
        <article class="refund-section-card" id="section-10">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">10</div>
              <h2 class="section-card-title">Payment Verification and Pending Transactions</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-10')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>A payment screenshot or submitted transaction ID alone does not establish that payment has been successfully received.</p>
            <p>If a payment remains pending or cannot be verified, the order may be held while we investigate.</p>
            <div class="refund-callout callout-warning">
              <i class="bi bi-exclamation-triangle-fill callout-icon"></i>
              <div>
                <strong>Important Advice:</strong> Customers should avoid making another payment for the same order unless support confirms that it is necessary.
              </div>
            </div>
            <p class="mb-0">If the payment is later confirmed but the order cannot be fulfilled, refund eligibility will be assessed under this policy.</p>
          </div>
        </article>

        <!-- Section 11 -->
        <article class="refund-section-card" id="section-11">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">11</div>
              <h2 class="section-card-title">Refunds for Redeem Codes and Subscriptions</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-11')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>Customers should check the product description, region, eligibility, validity period, and activation requirements before purchasing digital codes or subscriptions.</p>
            <p>If a delivered code or subscription fails due to a verified issue attributable to TOPUPERS or its supplier, contact support promptly.</p>
            
            <div class="refund-callout callout-info">
              <i class="bi bi-clock-history callout-icon"></i>
              <div>
                <strong>Recommended 48-Hour Reporting Window:</strong> Where practical, report code-related issues <strong>within 48 hours of delivery</strong> so we can investigate with the supplier. This is a recommended reporting timeframe, not a waiver of any mandatory legal rights.
              </div>
            </div>

            <p class="mb-0">Successfully redeemed codes, activated subscriptions, or consumed digital benefits are generally irreversible and may not qualify for a refund unless required by law or affected by a verified fulfillment error.</p>
          </div>
        </article>

        <!-- Section 12 -->
        <article class="refund-section-card" id="section-12">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">12</div>
              <h2 class="section-card-title">Refunds for Promotional and Discounted Orders</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-12')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>Promotional orders, coupon purchases, and discounted products are subject to the same basic refund principles as other orders.</p>
            <p>If a refund is approved, the amount refunded will be calculated according to the <strong>amount actually paid</strong> for the affected product, taking account of any applicable legal requirements.</p>
            <p class="mb-0">A promotional discount will not be treated as a reason to deny a refund that is otherwise legally or contractually due.</p>
          </div>
        </article>

        <!-- Section 13 -->
        <article class="refund-section-card" id="section-13">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">13</div>
              <h2 class="section-card-title">Fraudulent or Abusive Refund Claims</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-13')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>TOPUPERS may investigate refund requests involving suspected duplicate claims, altered receipts, false payment references, chargeback abuse, or fabricated delivery complaints.</p>
            <p>We may request reasonable evidence and take proportionate action where fraud is substantiated.</p>
            <p>A claim will not be rejected solely because it is inconvenient or because a customer has previously submitted a legitimate complaint.</p>
            <p class="mb-0">Nothing in this section limits a customer's lawful right to dispute a transaction or contact a competent authority.</p>
          </div>
        </article>

        <!-- Section 14 -->
        <article class="refund-section-card" id="section-14">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">14</div>
              <h2 class="section-card-title">Customer Support and Complaints</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-14')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>If you disagree with a refund decision, you may contact TOPUPERS support and request a further review.</p>
            <p>Please include your order number, the original decision, and any additional relevant evidence.</p>
            <p>We will review the matter fairly and respond within a reasonable timeframe.</p>
            <p class="mb-0">Customers retain any rights available through applicable consumer protection authorities or other lawful dispute-resolution channels.</p>
          </div>
        </article>

        <!-- Section 15 -->
        <article class="refund-section-card" id="section-15">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">15</div>
              <h2 class="section-card-title">Policy Updates</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-15')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>TOPUPERS may update this policy to reflect changes in products, supplier arrangements, payment services, or applicable laws.</p>
            <p>The latest version will be published on our website with its updated effective date.</p>
            <p class="mb-0">Changes will not retroactively remove rights that have already accrued to customers or override mandatory legal protections.</p>
          </div>
        </article>

        <!-- Section 16 -->
        <article class="refund-section-card" id="section-16">
          <div class="section-card-header">
            <div class="section-card-title-wrap">
              <div class="section-num-pill">16</div>
              <h2 class="section-card-title">Contact Information</h2>
            </div>
            <button class="btn-copy-anchor" onclick="copyAnchorLink('section-16')" title="Copy link to this section">
              <i class="bi bi-link-45deg"></i>
            </button>
          </div>
          <div class="refund-card-body">
            <p>For cancellations, failed orders, refund requests, duplicate payments, or complaints, contact:</p>

            <div class="p-3 rounded-3 mt-3 mb-3" style="background:var(--rx-surface-hover); border:1px solid var(--rx-border);">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                  <h4 style="font-size:1.05rem; font-weight:700; color:var(--rx-text); margin:0 0 4px;">TOPUPERS Refund &amp; Resolution Desk</h4>
                  <p class="mb-1" style="font-size:0.88rem; color:var(--rx-text-secondary);">
                    <strong>Email:</strong> <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary);"><?= e($siteEmail) ?></a>
                  </p>
                  <p class="mb-1" style="font-size:0.88rem; color:var(--rx-text-secondary);">
                    <strong>Website:</strong> <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" style="color:var(--rx-primary);">https://topupers.com</a>
                  </p>
                  <p class="mb-0" style="font-size:0.88rem; color:var(--rx-text-secondary);">
                    <strong>Support Channel:</strong> Official TOPUPERS Facebook page or the support channel published on our website.
                  </p>
                </div>
                <div class="d-flex gap-2">
                  <a href="<?= APP_URL ?>/contact.php" class="btn btn-primary btn-sm px-3" style="border-radius:8px;">
                    <i class="bi bi-headset me-1"></i> Submit Request
                  </a>
                  <a href="mailto:<?= e($siteEmail) ?>" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:8px;">
                    <i class="bi bi-envelope-fill me-1"></i> Email Desk
                  </a>
                </div>
              </div>
            </div>

            <p class="mb-3" style="font-size:0.92rem;"><strong>Important:</strong> Please include your <strong>order number</strong> when contacting support.</p>

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
<div id="refundToast" class="refund-toast">
  <i class="bi bi-check2-circle text-primary"></i>
  <span>Section link copied to clipboard!</span>
</div>

<!-- Interactive Client-side Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Reading Progress Bar
  const progressBar = document.getElementById('refundProgressBar');
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
  const sections = document.querySelectorAll('.refund-section-card');
  const tocLinks = document.querySelectorAll('.refund-toc-link');

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
        const tocList = document.getElementById('refundTocList');
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
  const searchInput = document.getElementById('refundSearchInput');
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
  const toast = document.getElementById('refundToast');
  if (!toast) return;
  toast.querySelector('span').innerText = msg;
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 2400);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
