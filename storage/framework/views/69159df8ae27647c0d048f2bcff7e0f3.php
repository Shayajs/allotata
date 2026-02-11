<!DOCTYPE html>
<html lang="fr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title><?php echo e($subject ?? 'Allo Tata'); ?></title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        
        /* Base styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
            line-height: 1.6;
            color: #374151;
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            background-color: #f3f4f6;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Container */
        .email-wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 40px 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #f97316 100%);
            padding: 32px 40px;
            text-align: center;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .logo-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 8px;
            font-weight: 500;
        }
        
        /* Content */
        .content {
            padding: 40px;
        }
        
        .content h1 {
            color: #111827;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 20px 0;
            line-height: 1.3;
        }
        
        .content h2 {
            color: #1f2937;
            font-size: 20px;
            font-weight: 600;
            margin: 24px 0 16px 0;
        }
        
        .content h3 {
            color: #374151;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 12px 0;
        }
        
        .content p {
            margin: 0 0 16px 0;
            color: #4b5563;
            font-size: 16px;
            line-height: 1.7;
        }
        
        .content ul, .content ol {
            margin: 0 0 16px 0;
            padding-left: 24px;
        }
        
        .content li {
            margin-bottom: 8px;
            color: #4b5563;
            font-size: 16px;
        }
        
        .content a {
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
        }
        
        .content a:hover {
            color: #16a34a;
            text-decoration: underline;
        }
        
        /* Buttons */
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 14px 0 rgba(34, 197, 94, 0.39);
            transition: all 0.2s ease;
        }
        
        .button:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            box-shadow: 0 6px 20px 0 rgba(34, 197, 94, 0.5);
        }
        
        .button-secondary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            box-shadow: 0 4px 14px 0 rgba(249, 115, 22, 0.39);
        }
        
        .button-secondary:hover {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            box-shadow: 0 6px 20px 0 rgba(249, 115, 22, 0.5);
        }
        
        /* Info boxes */
        .info-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
            border-left: 4px solid #22c55e;
            border-radius: 0 12px 12px 0;
            padding: 20px 24px;
            margin: 24px 0;
        }
        
        .info-box h3 {
            color: #166534;
            margin-top: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .info-box p {
            color: #166534;
            margin-bottom: 8px;
        }
        
        .info-box p:last-child {
            margin-bottom: 0;
        }
        
        .warning-box {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 0 12px 12px 0;
            padding: 20px 24px;
            margin: 24px 0;
        }
        
        .warning-box h3 {
            color: #92400e;
            margin-top: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .warning-box p {
            color: #92400e;
            margin-bottom: 8px;
        }
        
        .warning-box p:last-child {
            margin-bottom: 0;
        }
        
        .error-box {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #ef4444;
            border-radius: 0 12px 12px 0;
            padding: 20px 24px;
            margin: 24px 0;
        }
        
        .error-box h3 {
            color: #991b1b;
            margin-top: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .error-box p {
            color: #991b1b;
            margin-bottom: 8px;
        }
        
        .error-box p:last-child {
            margin-bottom: 0;
        }
        
        /* Details card */
        .details-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        
        .details-card h3 {
            color: #111827;
            margin-top: 0;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .detail-value {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }
        
        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e5e7eb 50%, transparent 100%);
            margin: 32px 0;
        }
        
        /* Footer */
        .footer {
            background-color: #f9fafb;
            padding: 32px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer p {
            color: #6b7280;
            font-size: 13px;
            margin: 0 0 12px 0;
        }
        
        .footer-links {
            margin-top: 16px;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            margin: 0 12px;
        }
        
        .footer-links a:hover {
            color: #22c55e;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #9ca3af;
        }
        
        .social-links a:hover {
            color: #22c55e;
        }
        
        /* Signature */
        .signature {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .signature p {
            margin-bottom: 4px;
        }
        
        .signature .team-name {
            color: #22c55e;
            font-weight: 600;
        }
        
        /* Responsive */
        @media screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 10px;
            }
            
            .email-container {
                border-radius: 12px;
            }
            
            .header {
                padding: 24px 20px;
            }
            
            .logo {
                font-size: 26px;
            }
            
            .content {
                padding: 24px 20px;
            }
            
            .content h1 {
                font-size: 20px;
            }
            
            .button {
                display: block;
                padding: 14px 20px;
            }
            
            .footer {
                padding: 24px 20px;
            }
            
            .footer-links a {
                display: block;
                margin: 8px 0;
            }
        }
        
        /* Dark mode support (for email clients that support it) */
        @media (prefers-color-scheme: dark) {
            .email-wrapper {
                background-color: #1f2937 !important;
            }
            
            .email-container {
                background-color: #111827 !important;
            }
            
            .content h1, .content h2 {
                color: #f9fafb !important;
            }
            
            .content p, .content li {
                color: #d1d5db !important;
            }
            
            .details-card {
                background-color: #1f2937 !important;
                border-color: #374151 !important;
            }
            
            .footer {
                background-color: #1f2937 !important;
                border-color: #374151 !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td align="center">
                    <div class="email-container">
                        <!-- Header -->
                        <div class="header">
                            <?php
                                use App\Helpers\SiteHelper;
                                $emailLogo = SiteHelper::getEmailLogo();
                                $siteName = SiteHelper::getSiteName();
                            ?>
                            <?php if($emailLogo): ?>
                                <a href="<?php echo e(url('/')); ?>" style="display: inline-block; margin-bottom: 8px;">
                                    <img src="<?php echo e($emailLogo); ?>" alt="<?php echo e($siteName); ?>" style="max-width: 200px; height: auto; max-height: 60px;">
                                </a>
                            <?php else: ?>
                                <a href="<?php echo e(url('/')); ?>" class="logo"><?php echo e($siteName); ?></a>
                            <?php endif; ?>
                            <p class="logo-subtitle">Votre assistant professionnel</p>
                        </div>
                        
                        <!-- Content -->
                        <div class="content">
                            <?php echo $__env->yieldContent('content'); ?>
                        </div>
                        
                        <!-- Footer -->
                        <div class="footer">
                            <p>Cet email a été envoyé par <strong>Allo Tata</strong></p>
                            <p>Si vous ne souhaitez plus recevoir ces emails, vous pouvez modifier vos préférences dans votre compte.</p>
                            
                            <div class="footer-links">
                                <a href="<?php echo e(url('/')); ?>">Site web</a>
                                <a href="<?php echo e(route('legal.confidentialite')); ?>">Confidentialité</a>
                                <a href="<?php echo e(route('legal.cgu')); ?>">CGU</a>
                                <a href="<?php echo e(url('/contact')); ?>">Contact</a>
                            </div>
                            
                            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                                © <?php echo e(date('Y')); ?> Allo Tata. Tous droits réservés.
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/emails/layout.blade.php ENDPATH**/ ?>