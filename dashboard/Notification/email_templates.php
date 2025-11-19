<?php
/**
 * Email Templates Configuration
 * Edit the templates below to customize your email notifications
 */

return [
    // Discount/Promotion Template
    'discount' => [
        'subject' => 'Special Discount for PAWsig City Services! 🐾',
        'title' => 'Exclusive Discount Just for You!',
        'content' => '
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                We appreciate your loyalty to PAWsig City! 
            </p>
            <div style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); padding: 25px; text-align: center; margin: 25px 0; border-radius: 12px; box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3);">
                <h2 style="color: #fff; font-size: 32px; margin: 0 0 10px 0;">🎉 SPECIAL OFFER 🎉</h2>
                <p style="color: #fff; font-size: 24px; font-weight: bold; margin: 0;">20% OFF</p>
                <p style="color: #fff; font-size: 16px; margin: 10px 0 0 0;">All Grooming Services</p>
            </div>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                Book your appointment today and save on our premium grooming services! 
                Your furry friend deserves the best care.
            </p>
            <p style="font-size: 14px; color: #999; margin-top: 20px;">
                <strong>Promo Code:</strong> PAWSIG20<br>
                <strong>Valid Until:</strong> December 31, 2025
            </p>
        ',
        'cta_text' => 'Book Now',
        'cta_link' => 'https://yourwebsite.com/booking'
    ],

    // Closure Notice Template
    'closure' => [
        'subject' => 'Important Notice: PAWsig City Temporary Closure',
        'title' => 'Temporary Closure Notice',
        'content' => '
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                Dear Valued Customer,
            </p>
            <div style="background: #FFF3CD; padding: 20px; border-left: 4px solid #FFA500; margin: 20px 0; border-radius: 8px;">
                <h3 style="color: #856404; margin: 0 0 10px 0;">⚠️ Temporary Closure</h3>
                <p style="color: #856404; margin: 0; font-size: 15px;">
                    PAWsig City will be temporarily closed for maintenance and improvements.
                </p>
            </div>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                <strong>Closure Period:</strong><br>
                From: January 15, 2025<br>
                To: January 20, 2025
            </p>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                We apologize for any inconvenience this may cause. We\'re making these improvements 
                to serve you and your pets better!
            </p>
        ',
        'cta_text' => null,
        'cta_link' => null
    ],

    // Reopening Notice Template
    'reopening' => [
        'subject' => 'We\'re Back! PAWsig City is Now Open 🎊',
        'title' => 'We\'re Open Again!',
        'content' => '
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                Great news! PAWsig City has reopened and we\'re excited to welcome you back!
            </p>
            <div style="background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%); padding: 25px; text-align: center; margin: 25px 0; border-radius: 12px;">
                <h2 style="color: #2d5f4a; font-size: 36px; margin: 0;">🎊 WE\'RE OPEN! 🎊</h2>
                <p style="color: #2d5f4a; font-size: 18px; margin: 15px 0 0 0;">
                    Visit us today with exciting new improvements!
                </p>
            </div>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                <strong>New Features:</strong>
            </p>
            <ul style="font-size: 16px; color: #666; line-height: 1.8;">
                <li>Upgraded grooming facilities</li>
                <li>Enhanced comfort areas for your pets</li>
                <li>New spa treatments available</li>
            </ul>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                Book your appointment today and experience the difference!
            </p>
        ',
        'cta_text' => 'Schedule Appointment',
        'cta_link' => 'https://yourwebsite.com/booking'
    ],

    // General Announcement Template
    'announcement' => [
        'subject' => 'Important Update from PAWsig City',
        'title' => 'Important Announcement',
        'content' => '
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                Dear Valued Customer,
            </p>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                We have an important update to share with you regarding our services at PAWsig City.
            </p>
            <div style="background: #E8F5E9; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="color: #2d5f4a; margin: 0; font-size: 16px; line-height: 1.6;">
                    [Your announcement message goes here. Edit this in email_templates.php]
                </p>
            </div>
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                Thank you for your continued support and trust in PAWsig City.
            </p>
        ',
        'cta_text' => 'Learn More',
        'cta_link' => 'https://yourwebsite.com'
    ],

    // Custom Template (fully editable)
    'custom' => [
        'subject' => 'Message from PAWsig City',
        'title' => 'Special Notice',
        'content' => '
            <p style="font-size: 16px; color: #666; line-height: 1.6;">
                [Edit this template to create your custom message]
            </p>
        ',
        'cta_text' => null,
        'cta_link' => null
    ]
];