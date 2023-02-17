<?php

namespace Drupal\optional_end_date\Plugin\Field\FieldFormatter;

use Drupal\optional_end_date\OptionalEndDateDateTimeRangeTrait;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;

/**
 * Override the default formatter.
 */
class OptionalEndDateDateRangeDefaultFormatter extends DateRangeDefaultFormatter {

  use OptionalEndDateDateTimeRangeTrait;

}
