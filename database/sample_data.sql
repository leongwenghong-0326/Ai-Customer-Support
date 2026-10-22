-- NovaServe AI Intelligent Customer Support Platform
-- Fictional Seed and Demonstration Sample Data
-- Default user password for all seed accounts: Admin@123456

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insert Roles
INSERT INTO `roles` (`id`, `role_key`, `name`, `description`) VALUES
(1, 'super_admin', 'Super Administrator', 'Complete system control, AI configuration, staff and security oversight'),
(2, 'support_staff', 'Support Representative', 'Ticket management, live chat takeover, customer communication and internal notes'),
(3, 'content_manager', 'Content Specialist', 'Manages knowledge base articles, categories, and published FAQ resources'),
(4, 'customer', 'Registered Client', 'Customer portal user with AI chat access, ticket submission and profile features');

-- 2. Insert Users
INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `password_hash`, `phone`, `status`, `created_at`) VALUES
(1, 1, 'Alexander Vance', 'admin@novaserve.com', '$2y$10$p5IORX0Q7UgWjsPKyJDhQe9BGG4ig2Fmkk.nWGQmVphtF1sGhZVEy', '+1-800-555-0190', 'active', NOW()),
(2, 2, 'Elena Rostova', 'staff@novaserve.com', '$2y$10$p5IORX0Q7UgWjsPKyJDhQe9BGG4ig2Fmkk.nWGQmVphtF1sGhZVEy', '+1-800-555-0191', 'active', NOW()),
(3, 3, 'Marcus Sterling', 'content@novaserve.com', '$2y$10$p5IORX0Q7UgWjsPKyJDhQe9BGG4ig2Fmkk.nWGQmVphtF1sGhZVEy', '+1-800-555-0192', 'active', NOW()),
(4, 4, 'Sophia Chen', 'customer@example.com', '$2y$10$p5IORX0Q7UgWjsPKyJDhQe9BGG4ig2Fmkk.nWGQmVphtF1sGhZVEy', '+1-555-234-8899', 'active', NOW()),
(5, 4, 'Liam Gallagher', 'liam.g@example.com', '$2y$10$p5IORX0Q7UgWjsPKyJDhQe9BGG4ig2Fmkk.nWGQmVphtF1sGhZVEy', '+1-555-891-2300', 'active', NOW()),
(6, 4, 'Amira Al-Mansoor', 'amira@example.com', '$2y$10$p5IORX0Q7UgWjsPKyJDhQe9BGG4ig2Fmkk.nWGQmVphtF1sGhZVEy', '+1-555-334-1122', 'active', NOW());

-- 3. Insert Categories (5+ categories)
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `sort_order`, `is_active`) VALUES
(1, 'Billing & Subscriptions', 'billing-subscriptions', 'fa-credit-card', 'Invoices, payment gateways, pricing tiers, renewals and receipts', 1, 1),
(2, 'Account & Security', 'account-security', 'fa-shield-halved', 'Authentication, password recovery, 2FA credentials and permissions', 2, 1),
(3, 'Cloud Platform & API', 'cloud-platform-api', 'fa-network-wired', 'API keys, webhooks, rate limits, latency troubleshooting and SDKs', 3, 1),
(4, 'Returns & Refund Policy', 'returns-refund-policy', 'fa-arrow-rotate-left', 'Dispute timelines, refund eligibility, cancellations and billing credits', 4, 1),
(5, 'Technical Troubleshooting', 'technical-troubleshooting', 'fa-wrench', 'System status, connectivity glitches, browser voice support and error codes', 5, 1);

-- 4. Insert FAQ Items (10+ FAQs)
INSERT INTO `faq_items` (`id`, `category_id`, `question`, `answer`, `sort_order`, `views_count`, `is_active`, `created_by`) VALUES
(1, 1, 'What payment methods does NovaServe AI accept?', 'We accept major credit cards (Visa, MasterCard, American Express), PayPal, and enterprise direct wire transfers (ACH/SEPA) on annual commercial plans.', 1, 312, 1, 3),
(2, 1, 'How do I download my monthly tax invoice?', 'Log in to your Customer Portal, go to Account Settings > Invoices, and click the PDF download button beside any billing cycle statement.', 2, 189, 1, 3),
(3, 1, 'Can I upgrade or downgrade my tier at any time?', 'Yes. Plan upgrades take effect immediately with prorated billing. Downgrades take effect at the conclusion of your current active billing period.', 3, 145, 1, 3),
(4, 2, 'How do I reset my NovaServe account password?', 'Click "Forgot Password" on the login screen, enter your registered email address, and follow the secure single-use recovery link dispatched to your inbox within 15 minutes.', 1, 472, 1, 3),
(5, 2, 'Can I activate Two-Factor Authentication (2FA)?', 'Yes, go to your Profile > Security, select "Enable Authenticator 2FA", and scan the QR code using Google Authenticator, Authy, or Microsoft Authenticator.', 2, 260, 1, 3),
(6, 3, 'Where can I locate my API keys?', 'Administrators can navigate to Developer Portal > API Access Keys to view, generate, rotate, or revoke production and sandbox API tokens.', 1, 388, 1, 3),
(7, 3, 'What are the default REST API rate limits?', 'Standard business accounts are allocated 120 requests per minute with a burst allowance of 200 req/min. Enterprise plans have custom negotiated rate ceilings.', 2, 195, 1, 3),
(8, 4, 'What is the NovaServe 30-day refund guarantee policy?', 'New customers are eligible for a 100% money-back refund within the first 30 days of subscription activation if our platform fails to meet your technical requirements.', 1, 520, 1, 3),
(9, 4, 'How long does it take for a refund to process?', 'Approved refunds are credited back to your original payment method within 5 to 7 business days, depending on your financial institution.', 2, 210, 1, 3),
(10, 5, 'Why is my voice microphone button not responding in chat?', 'Ensure your web browser has granted microphone access permissions for this domain. The Web Speech API is natively supported on modern Chrome, Edge, and Safari.', 1, 410, 1, 3),
(11, 5, 'What are NovaServe official support operating hours?', 'Our AI assistant operates 24/7/365. Human tier-2 support engineers are on duty Monday through Friday, 08:00 AM to 08:00 PM EST, with urgent on-call coverage on weekends.', 2, 630, 1, 3);

-- 5. Insert Knowledge Articles (10+ Articles)
INSERT INTO `knowledge_articles` (`id`, `category_id`, `title`, `slug`, `summary`, `content`, `tags`, `views_count`, `status`, `created_by`) VALUES
(1, 1, 'Understanding NovaServe SaaS Subscription Billing Cycles', 'understanding-subscription-billing-cycles', 'A complete walkthrough of recurring billing, billing anniversary dates, and automated credit card processing.', '<p>NovaServe AI bills on a predictable monthly or annual cadence. All recurring subscriptions are processed on the anniversary date of your initial subscription sign-up.</p><h3>Key Points</h3><ul><li><strong>Proration:</strong> Adding seats or upgrading computing power mid-month calculates an exact per-day proration adjustment.</li><li><strong>Grace Period:</strong> In the event of a failed card authorization, our automated retry system attempts three retries over 5 calendar days before service throttling.</li></ul>', 'billing,subscription,invoice,proration', 540, 'published', 3),
(2, 1, 'Enterprise Invoicing and Tax Exemption Guidelines', 'enterprise-invoicing-tax-exemption', 'Details on submitting W-9 forms, VAT identification, and setting up net-30 purchase orders for institutional clients.', '<p>Enterprise organizations requiring Net-30 purchase orders can request corporate invoicing directly through our accounts team.</p><h3>Tax Exempt Status</h3><p>If your organization is a qualified 501(c)(3) non-profit or holds state sales tax exemption certificates, please submit an official certificate copy via support ticket before invoice finalization.</p>', 'enterprise,tax,vat,net30,invoice', 230, 'published', 3),
(3, 2, 'Best Practices for Securing Your Customer Support Portal', 'best-practices-securing-portal', 'Critical recommendations for role-based account hygiene, strong passphrase policies, and session duration limits.', '<p>Security is a shared responsibility. NovaServe incorporates zero-trust principles across all authentication endpoints.</p><h3>Recommended Security Controls</h3><ol><li>Enforce hardware or software TOTP authenticators for all supervisor and support staff accounts.</li><li>Rotate administrator API tokens at least once every 90 days.</li><li>Never share support staff credentials across individual team representatives.</li></ol>', 'security,passwords,2fa,best-practices', 390, 'published', 3),
(4, 2, 'Managing Role-Based Access Control (RBAC) Permissions', 'managing-rbac-permissions', 'How to assign Super Admin, Support Representative, and Content Specialist privileges to staff members.', '<p>NovaServe AI enforces strict access isolation. Roles define what views, endpoints, and administrative data operators can inspect.</p><ul><li><strong>Super Admin:</strong> Full governance over billing, AI model parameters, logs, and user directory.</li><li><strong>Support Staff:</strong> Handles customer tickets, conversation takeover, and internal resolution notes.</li><li><strong>Content Manager:</strong> Curates FAQs and Knowledge Base articles without access to customer confidential tickets.</li></ul>', 'rbac,roles,permissions,staff', 415, 'published', 3),
(5, 3, 'Connecting to the NovaServe REST API: Quickstart Guide', 'rest-api-quickstart-guide', 'Step-by-step instructions for authenticating, dispatching payloads, and handling response JSON schemas.', '<p>The NovaServe REST API allows seamless CRM and helpdesk synchronization. All requests must transmit your Bearer authorization token in the HTTP header:</p><pre><code>Authorization: Bearer YOUR_API_TOKEN\nContent-Type: application/json</code></pre><p>All responses follow the standard JSON payload structure with status codes and timestamped correlation IDs.</p>', 'api,rest,developer,integration', 612, 'published', 3),
(6, 3, 'Webhook Integration and Event Notification Triggers', 'webhook-integration-triggers', 'Configure outbound HTTP POST webhooks for ticket status changes, customer escalations, and AI feedback alarms.', '<p>Receive instant notifications in your internal Slack, Microsoft Teams, or custom webhook endpoint whenever key events fire.</p><h3>Supported Events</h3><ul><li><code>ticket.created</code>: Triggers when a new customer inquiry is lodged.</li><li><code>conversation.escalated</code>: Fires when sentiment analysis flags an angry customer or a customer requests human takeover.</li><li><code>ai.fallback_invoked</code>: Telemetry alert when primary AI experiences upstream outages.</li></ul>', 'webhooks,events,automation,slack', 320, 'published', 3),
(7, 4, 'Comprehensive Customer Refund and Cancellation Policy', 'comprehensive-refund-cancellation-policy', 'Official guidelines on the 30-day satisfaction guarantee, cancellation deadlines, and credit processing terms.', '<p>At NovaServe, customer satisfaction is our paramount commitment. If our AI support platform does not meet your operational expectations, you may cancel anytime.</p><h3>30-Day Money-Back Guarantee</h3><p>First-time subscribers are entitled to a full 100% refund within the initial 30 calendar days. Subsequent recurring renewals are non-refundable once the cycle has commenced, but service remains uninterrupted until the period end.</p>', 'refund,cancellation,guarantee,policy', 890, 'published', 3),
(8, 4, 'Handling Dispute Resolution and Chargeback Safeguards', 'dispute-resolution-chargebacks', 'Recommended procedures for resolving billing discrepancies directly with our finance team prior to bank disputes.', '<p>We encourage customers to submit a high-priority billing inquiry before initiating a merchant dispute. Bank disputes typically freeze account access for up to 60 days during financial review. Our billing team resolves over 98% of discrepancies within 24 business hours.</p>', 'disputes,chargeback,billing,resolution', 178, 'published', 3),
(9, 5, 'Browser Speech Recognition and Audio Synthesis Guide', 'browser-speech-recognition-guide', 'How to leverage the built-in microphone and voice output features, with microphone permission troubleshooting.', '<p>NovaServe AI is equipped with hands-free voice interaction built on the W3C Web Speech API standard.</p><h3>Troubleshooting Voice Input</h3><p>If you click the microphone and do not observe transcription:</p><ol><li>Verify that browser permissions permit microphone access for this origin.</li><li>Ensure HTTPS is enabled on production servers, as modern browsers restrict Web Speech API on unencrypted HTTP connections.</li><li>Check that your system default input microphone device is active and not muted.</li></ol>', 'voice,speech,microphone,synthesis,audio', 720, 'published', 3),
(10, 5, 'Understanding AI Sentiment Detection and Escalation Workflow', 'sentiment-detection-escalation-workflow', 'How NovaServe analyzes customer text to gauge sentiment and automatically escalate urgent matters to human staff.', '<p>NovaServe employs linguistic sentiment analysis across all inbound interactions. Messages are classified into Positive, Neutral, Negative, or Angry.</p><p>When an interaction is categorized as <strong>Angry</strong> or confidence falls below the system threshold, our AI gracefully offers human assistance and routes the live conversation to the Support Representative takeover queue.</p>', 'sentiment,ai,escalation,confidence,workflow', 580, 'published', 3),
(11, 4, 'NovaServe AI Terms of Service & User Agreement', 'terms-of-service', 'Official terms of service governing usage of the NovaServe AI customer support platform, service level commitments, and licensing.', '<h2>NovaServe AI Terms of Service</h2><p>Welcome to NovaServe AI. By accessing or utilizing our platform, customer portal, APIs, and AI support modules, you agree to be bound by these Terms of Service.</p><h3>1. Permitted Use</h3><p>Authorized users may utilize the platform solely for internal business operations and customer support engagements.</p><h3>2. Data & Confidentiality</h3><p>NovaServe implements enterprise-grade isolation. Customer interaction records and support tickets are protected under strict confidentiality agreements.</p><h3>3. Service Levels</h3><p>We target 99.9% uptime across all cloud inference infrastructure and real-time support pipelines.</p>', 'terms,legal,agreement,tos,service', 120, 'published', 3),
(12, 2, 'NovaServe AI Privacy Policy & Data Security Standards', 'privacy-policy', 'Comprehensive privacy policy detailing data encryption, retention schedules, 2FA safeguards, and personal data handling.', '<h2>NovaServe AI Privacy Policy</h2><p>Your privacy and data integrity are central to our system design. This policy outlines how information is gathered, encrypted, and retained.</p><h3>1. Data Collection & Security</h3><p>All transmitted payloads undergo TLS 1.3 encryption in transit and AES-256 encryption at rest.</p><h3>2. Authentication & Credential Safeguards</h3><p>NovaServe strictly enforces bcrypt hashed passwords and optional Time-based One-Time Password (TOTP) two-factor authentication.</p><h3>3. Compliance</h3><p>Our data handling aligns with global privacy best practices, including rights to data export, audit logging, and account deletion upon request.</p>', 'privacy,security,gdpr,data,encryption,policy', 185, 'published', 3);

-- 6. Insert AI Settings
INSERT INTO `ai_settings` (`setting_key`, `setting_value`, `description`) VALUES
('ai_enabled', '1', 'Master switch to enable or disable AI assistant responses (1=enabled, 0=disabled)'),
('primary_api_url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent', 'Primary REST endpoint for AI inference engine (Google Gemini API)'),
('primary_api_key', 'YOUR_GEMINI_API_KEY_HERE', 'Private API key for the primary AI provider (securely stored server-side)'),
('primary_model', 'gemini-3.8-flash', 'Default model identifier for primary inference'),
('fallback_api_url', 'https://apihub.agnes-ai.com/v1/chat/completions', 'Secondary fallback API endpoint if primary experiences network timeout (Agnes AI)'),
('fallback_api_key', 'YOUR_AGNES_API_KEY_HERE', 'Private API key for fallback AI provider'),
('fallback_model', 'agnes-2.5-flash', 'Default model identifier for secondary inference'),
('temperature', '0.3', 'Sampling temperature controlling AI response creativity (0.0=deterministic, 1.0=creative)'),
('max_tokens', '800', 'Maximum token limit allocated per single response generation'),
('confidence_threshold', '65.0', 'Minimum percentage confidence score before triggering human support offer'),
('escalation_on_angry', '1', 'Automatically recommend human takeover when customer sentiment is Angry'),
('system_prompt', 'You are Nova, the official intelligent AI customer service representative for NovaServe AI. You are courteous, highly knowledgeable, articulate, and concise. Your primary directive is to answer customer questions using verified organizational knowledge base articles and official FAQ entries provided in the context. Never fabricate pricing, policies, contact numbers, or system credentials. If the information is unavailable, politely state so and offer to connect them with human support. Match the customer\'s language (English, Bahasa Melayu, or Chinese) respectfully.', 'System prompt guiding the behavior and tone of NovaServe AI');

-- 7. Insert System Settings
INSERT INTO `system_settings` (`config_key`, `config_value`, `description`) VALUES
('company_name', 'NovaServe AI Technologies', 'Official company and platform branding name'),
('company_tagline', 'Next-Generation Intelligent Customer Support & Service Automation', 'Primary marketing slogan and banner headline'),
('contact_email', 'support@novaserve.com', 'Official customer support email address'),
('contact_phone', '+1 (800) 555-NOVA (6682)', 'Toll-free customer assistance contact phone'),
('business_hours', 'Monday - Friday: 8:00 AM - 8:00 PM EST | Weekends: 10:00 AM - 4:00 PM EST', 'Official published operating schedule for live human support'),
('default_language', 'en', 'Default system interface language (en, ms, zh)'),
('max_upload_size_mb', '5', 'Maximum permissible file upload size in megabytes'),
('allowed_upload_extensions', 'jpg,jpeg,png,pdf,doc,docx,txt', 'Comma-separated whitelist of permissible attachment extensions'),
('installer_locked', '0', 'Flag indicating whether installation wizard has been completed (1=locked)');

-- 8. Insert Sample Conversations & Messages
INSERT INTO `conversations` (`id`, `customer_id`, `session_token`, `status`, `channel`, `language`, `primary_intent`, `overall_sentiment`, `confidence_score`, `assigned_staff_id`, `provider_used`, `started_at`) VALUES
(1, 4, 'sess_demo_alpha_01', 'resolved', 'web_chat', 'en', 'Refund', 'Positive', 94.50, NULL, 'Primary AI', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 5, 'sess_demo_beta_02', 'escalated', 'web_chat', 'en', 'Complaint', 'Angry', 55.00, 2, 'Primary AI', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 6, 'sess_demo_gamma_03', 'active', 'web_chat', 'en', 'Pricing', 'Neutral', 88.00, NULL, 'Local Knowledge', DATE_SUB(NOW(), INTERVAL 20 MINUTE));

INSERT INTO `conversation_messages` (`conversation_id`, `sender_type`, `sender_id`, `message_text`, `sources_json`, `intent`, `sentiment`, `confidence_score`, `is_voice_input`, `created_at`) VALUES
(1, 'customer', 4, 'Hello! What is your refund policy if I am not satisfied with the platform?', NULL, 'Refund', 'Neutral', NULL, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'ai', NULL, 'Hello Sophia! NovaServe offers a 100% money-back guarantee within the first 30 days of subscription activation. If our platform does not suit your operational requirements, simply submit a ticket to receive a full refund.', '[{"type":"faq","id":8,"title":"What is the NovaServe 30-day refund guarantee policy?"}]', 'Refund', 'Positive', 94.50, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'customer', 5, 'My API key stopped working and our production server is failing! This is unacceptable!', NULL, 'Complaint', 'Angry', NULL, 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(2, 'ai', NULL, 'I sincerely apologize for this disruption to your production systems. Because this requires immediate investigation, I have escalated this conversation directly to our senior human support engineers.', '[{"type":"article","id":5,"title":"Connecting to the NovaServe REST API: Quickstart Guide"}]', 'Complaint', 'Angry', 55.00, 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 'customer', 6, 'Hi there, where can I download invoices for tax deductions?', NULL, 'Pricing', 'Neutral', NULL, 0, DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
(3, 'ai', NULL, 'You can download monthly tax invoices directly from your Customer Portal under Account Settings > Invoices. Each statement is available as a signed PDF.', '[{"type":"faq","id":2,"title":"How do I download my monthly tax invoice?"}]', 'Pricing', 'Neutral', 88.00, 0, DATE_SUB(NOW(), INTERVAL 19 MINUTE));

-- 9. Insert Sample Feedback
INSERT INTO `conversation_feedback` (`conversation_id`, `message_id`, `customer_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 4, 'positive', 'Clear and concise answer regarding the 30-day guarantee.', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- 10. Insert Sample Support Tickets
INSERT INTO `support_tickets` (`id`, `ticket_number`, `customer_id`, `category_id`, `assigned_staff_id`, `subject`, `description`, `priority`, `status`, `created_at`) VALUES
(1, 'NS-202610-000001', 4, 1, 2, 'Inquiry regarding annual subscription discount', 'We are looking to migrate 25 team members to NovaServe and would like to understand whether annual volume discounting applies.', 'medium', 'in_progress', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'NS-202610-000002', 5, 3, 2, 'API rate limit increase request for batch job', 'Our data pipeline requires a temporary rate limit adjustment to 500 requests/minute during end-of-month synchronization.', 'high', 'open', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(3, 'NS-202610-000003', 6, 2, NULL, 'Assistance enabling Two-Factor Authentication', 'I received an error scanning the QR code on my authenticator app and would appreciate a manual entry seed key.', 'low', 'pending', DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- 11. Insert Sample Ticket Messages & Internal Notes
INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_id`, `message_text`, `is_internal_note`, `created_at`) VALUES
(1, 1, 4, 'Hello team, we are planning to migrate our 25-seat support desk to NovaServe. Could you share the annual enterprise quote?', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 1, 2, 'Customer operates an active SaaS business with good credit. Approved standard 20% annual bulk discount tier.', 1, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(3, 1, 2, 'Hi Sophia, thank you for reaching out! We are delighted to assist. For 25 seats on an annual billing schedule, we provide a 20% volume incentive. I have sent the detailed quote to your account email.', 0, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(4, 2, 5, 'Production batch synchronization requires higher burst thresholds between 02:00 AM and 04:00 AM UTC.', 0, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- 12. Insert Sample Notifications
INSERT INTO `notifications` (`user_id`, `role_target`, `title`, `message`, `type`, `reference_id`, `is_read`, `created_at`) VALUES
(NULL, 'support_staff', 'Angry Customer Escalation', 'Conversation #2 flagged with Angry sentiment: API key failure reported by Liam Gallagher.', 'angry_customer', 2, 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(NULL, 'support_staff', 'New High Priority Ticket', 'Ticket NS-202610-000002 submitted by Liam Gallagher requires review.', 'new_ticket', 2, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'super_admin', 'System Initialized', 'NovaServe AI Intelligent Platform deployed and initialized successfully.', 'system_alert', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- 13. Insert Sample Activity Logs
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 'System Installed', 'Initial database schema and default configuration created successfully.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'Admin Login', 'Alexander Vance logged in to Administration Center.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'Article Published', 'Marcus Sterling published article: Comprehensive Customer Refund Policy.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'Conversation Takeover', 'Elena Rostova inspected escalated conversation #2.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 2 HOUR));

SET FOREIGN_KEY_CHECKS = 1;
