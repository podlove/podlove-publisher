<?php

/**
 * This file is part of Twig.
 *
 * (c) 2014-2019 Fabien Potencier
 * (c) 2022-2022 Stéphane Férey
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Podlove\Template;

use DateTime;
use PodlovePublisher_Vendor\Twig\Environment;
use PodlovePublisher_Vendor\Twig\Extension\AbstractExtension;
use PodlovePublisher_Vendor\Twig\TwigFilter;

/**
 * @author Robin van der Vleuten <robinvdvleuten@gmail.com>
 */
final class DateExtension extends AbstractExtension
{
    public static $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('time_diff', [$this, 'diff'], ['needs_environment' => true]),
        ];
    }

    /**
     * Filters for converting dates to a time ago string like Facebook and Twitter has.
     *
     * @param \DateTime|string $date a string or DateTime object to convert
     * @param \DateTime|string $now  A string or DateTime object to compare with. If none given, the current time will be used.
     *
     * @return string the converted time
     */
    public function diff(Environment $env, $date, $now = null): string
    {
        // Convert both dates to DateTime instances.
        $date = \PodlovePublisher_Vendor\twig_date_converter($env, $date);
        $now = \PodlovePublisher_Vendor\twig_date_converter($env, $now);

        // Get the difference between the two DateTime objects.
        $diff = $date->diff($now);

        // Check for each interval if it appears in the $diff object.
        foreach (self::$units as $attribute => $unit) {
            $count = $diff->{$attribute};

            if (0 !== $count) {
                return $this->getPluralizedInterval($count, $diff->invert, $unit);
            }
        }

        return '';
    }

    private function getPluralizedInterval(int $count, $invert, $unit): string
    {
        if (1 !== $count) {
            $unit .= 's';
        }

        return $invert ? "in {$count} {$unit}" : "{$count} {$unit} ago";
    }
}
