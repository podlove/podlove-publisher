<?php
namespace Podlove\Model;

class DownloadIntent extends Base {

	public function add_geo_data($ip_string) {

		try {
			$reader = new \GeoIp2\Database\Reader(\Podlove\Geo_Ip::get_upload_file_path());
		} catch (\InvalidArgumentException $e) {
			return $this;
		}
		
		try {
			// geo ip lookup
			$record = $reader->city($ip_string);

			$this->lat = $record->location->latitude;
			$this->lng = $record->location->longitude;

			/**
			 * Get most specific area for given record, beginning at the given area-type.
			 *
			 * Missing records will be created on the fly, based on data in $record.
			 * 
			 * @param object $record GeoIp object
			 * @param string $type Area identifier. One of: city, subdivision, country, continent.
			 */
			$get_area = function($record, $type) use (&$get_area) {

				// get parent area for the given area-type
				$get_parent_area = function($type) use ($get_area, $record) {

					switch ($type) {
						case 'city':
							return $get_area($record, 'subdivision');
							break;
						case 'subdivision':
							return $get_area($record, 'country');
							break;
						case 'country':
							return $get_area($record, 'continent');
							break;
						case 'continent':
							// has no parent
							break;
					}

					return null;
				};

				$subRecord = $record->{$type == 'subdivision' ? 'mostSpecificSubdivision' : $type};

				if (!$subRecord->geonameId)
					return $get_parent_area($type);

				if ($area = GeoArea::find_one_by_property('geoname_id', $subRecord->geonameId))
					return $area;

				$area = new GeoArea;
				$area->geoname_id = $subRecord->geonameId;
				$area->type = $type;

				if (isset($subRecord->code)) {
					$area->code = $subRecord->code;
				} elseif (isset($subRecord->isoCode)) {
					$area->code = $subRecord->isoCode;
				}

				if ($area->type != 'continent') {
					$parent_area     = $get_parent_area($area->type);
					$area->parent_id = $parent_area->id;
				}

				$area->save();

				// save name and translations
				foreach ($subRecord->names as $lang => $name) {
					$n           = new GeoAreaName;
					$n->area_id  = $area->id;
					$n->language = $lang;
					$n->name     = $name;
					$n->save();
				}

				return $area;
			};

			$area = $get_area($record, 'city');

			$this->geo_area_id = $area->id;
		} catch (\GeoIp2\Exception\AddressNotFoundException $e) {
			// geo lookup might fail, but that's not grave		
		}

		return $this;
	}

}

DownloadIntent::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
DownloadIntent::property( 'user_agent_id', 'INT' );
DownloadIntent::property( 'media_file_id', 'INT' );
DownloadIntent::property( 'request_id', 'VARCHAR(32)' );
DownloadIntent::property( 'accessed_at', 'DATETIME' );
DownloadIntent::property( 'source', 'VARCHAR(255)' );
DownloadIntent::property( 'context', 'VARCHAR(255)' );
DownloadIntent::property( 'geo_area_id', 'INT' );
DownloadIntent::property( 'lat', 'FLOAT' );
DownloadIntent::property( 'lng', 'FLOAT' );
DownloadIntent::property( 'httprange', 'VARCHAR(255)' );