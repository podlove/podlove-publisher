<?php 
namespace Podlove\Modules\PodloveWebPlayer;

use Podlove\Model\Episode;

/**
 * Interface for webplayer Printer.
 * 
 * Every web player must provide a printer so it can be accessed
 * in shortcodes, templates etc.
 * 
 * Example:
 * 
 * class Printer implements PlayerPrinterInterface {
 *   
 *   public function __construct(Episode $episode) {
 *     $this->episode = $episode;
 *   }
 * 
 *   public function render($context = null) {
 *     return '<audio><source src="http://example.com/demo.m4a" type="audio/mp4"/></audio>';
 *   } 
 * 
 * }
 */
interface PlayerPrinterInterface {

	/**
	 * Constructor takes episode for player.
	 * 
	 * @param Episode $episode
	 */
	public function __construct(Episode $episode);

	/**
	 * Return rendered player HTML.
	 * 
	 * @param  string $context Optional string context. Correct header ist `$context = null`
	 * @return string
	 */
	public function render($context);

}
