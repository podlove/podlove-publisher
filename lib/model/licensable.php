<?php
namespace Podlove\Model;

interface Licensable {
	public function get_license();
	public function get_license_picture_url();
	public function get_license_html();
}