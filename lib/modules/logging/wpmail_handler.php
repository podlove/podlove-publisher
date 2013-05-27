<?php
namespace Podlove\Modules\Logging;

use Monolog\Logger;
use Monolog\Handler\MailHandler;

use Podlove\Model;

/**
 * WPMailHandler uses the wp_mail() function to send the emails
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class WPMailHandler extends MailHandler {

    protected $to;
    protected $subject;
    protected $headers = array(
        'Content-type: text/plain; charset=utf-8'
    );

    /**
     * @param string|array $to      The receiver of the mail
     * @param string       $subject The subject of the mail
     * @param integer      $level   The minimum logging level at which this handler will be triggered
     * @param boolean      $bubble  Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct( $to, $subject, $level = Logger::ERROR, $bubble = true) {
        parent::__construct( $level, $bubble );
        $this->to = is_array( $to ) ? $to : array( $to );
        $this->subject = $subject;
    }

    /**
     * {@inheritdoc}
     */
    protected function send( $content, array $records ) {

        $record = $records[0];

        $content = wordwrap( $content, 70 );

        if ( isset( $record['context']['episode_id'] ) ) {
            $episode = Model\Episode::find_by_id( $record['context']['episode_id'] );
            file_put_contents('/tmp/php.log', print_r($episode, true), FILE_APPEND | LOCK_EX);
            $content .= "\n\n" . wp_specialchars_decode( get_edit_post_link( $episode->post_id ) );
        }

        foreach ( $this->to as $to )
            wp_mail( $to, $this->subject, $content );
    }
}