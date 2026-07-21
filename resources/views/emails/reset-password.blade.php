<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
</head>
<body style="margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, sans-serif; color: #111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f3f4f6; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; background: #ffffff; border-radius: 8px; overflow: hidden;">
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="margin: 0 0 16px; font-size: 24px; line-height: 1.25; color: #111827;">
                                Reset your ProgressLab password
                            </h1>

                            <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.5;">
                                Hello {{ $user->name }},
                            </p>

                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.5;">
                                Use the button below to choose a new password.
                            </p>

                            <p style="margin: 0 0 24px;">
                                <a href="{{ $resetUrl }}" style="display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; border-radius: 6px;">
                                    Reset password
                                </a>
                            </p>

                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.5; color: #4b5563;">
                                This link expires in 5 minutes. If you did not request this, you can ignore this email.
                            </p>

                            <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #6b7280; word-break: break-all;">
                                If the button does not work, copy this link:<br>
                                <a href="{{ $resetUrl }}" style="color: #2563eb;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
