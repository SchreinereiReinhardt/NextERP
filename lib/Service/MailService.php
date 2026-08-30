<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use OCP\Mail\IMailer;

final class MailService {
    public function __construct(private IMailer $mailer) {}

    /**
     * @param array<int,array{name:string,data:string,mime:string}> $attachments
     */
    public function send(string $to,string $subject,string $body,array $attachments=[],?string $replyTo=null):void {
        $to=trim($to);
        if($to==='' || !filter_var($to,FILTER_VALIDATE_EMAIL)){
            throw new \InvalidArgumentException('Bitte eine gültige Empfänger-E-Mail-Adresse angeben.');
        }
        $subject=trim($subject);
        if($subject===''){
            throw new \InvalidArgumentException('Der E-Mail-Betreff darf nicht leer sein.');
        }

        $message=$this->mailer->createMessage();
        $message->setTo([$to]);
        $message->setSubject($subject);
        $message->setPlainBody($body);

        if($replyTo!==null && filter_var(trim($replyTo),FILTER_VALIDATE_EMAIL)){
            $message->setReplyTo([trim($replyTo)]);
        }

        foreach($attachments as $attachment){
            $message->attach(
                $this->mailer->createAttachment(
                    $attachment['data'],
                    $attachment['name'],
                    $attachment['mime']
                )
            );
        }

        $failed=$this->mailer->send($message);
        if($failed!==[]){
            throw new \RuntimeException('E-Mail konnte nicht an alle Empfänger zugestellt werden: '.implode(', ',$failed));
        }
    }
}
