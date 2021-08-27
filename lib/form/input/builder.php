<?php

namespace Podlove\Form\Input;

class Builder
{
    /**
     * Model record.
     *
     * @var object
     */
    public $object;

    /**
     * Form field name prefix.
     *
     * @var string
     */
    public $context;

    public $object_key;
    public $arguments;

    public function __construct($object, $context)
    {
        $this->object = $object;
        $this->context = $context;
    }

    public function get_field_name()
    {
        return ($this->context) ? "{$this->context}[{$this->object_key}]" : $this->object_key;
    }

    public function get_field_id()
    {
        if ($this->context) {
            $id = "{$this->context}_{$this->object_key}";
        } else {
            $id = $this->object_key;
        }

        $id = str_replace(['[', ']'], '_', $id);

        return str_replace('__', '_', $id);
    }

    public function get_extra_html_attributes()
    {
        if (!isset($this->arguments['html']) || !is_array($this->arguments['html'])) {
            return '';
        }

        $compiled_html = '';

        foreach ($this->arguments['html'] as $key => $value) {
            $compiled_html .= "{$key}=\"{$value}\" ";
        }

        return $compiled_html;
    }

    public function string($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<div>
			<input type="<?php echo (isset($arguments['type']) && $arguments['type']) ? $arguments['type'] : 'text'; ?>" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr($this->field_value); ?>" <?php echo $this->html_attributes; ?><?php echo !empty($arguments['type']) && 'number' == $arguments['type'] && !empty($arguments['positive_number']) ? ' onkeypress="return event.charCode >= 48" min="0"' : ''; ?>><span class="podlove-input-status" data-podlove-input-status-for="<?php echo $this->field_id; ?>"></span>
		</div>
		<?php
    }

    public function color($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<div>
			<input type="color" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr($this->field_value); ?>" <?php echo $this->html_attributes; ?>><span class="podlove-input-status" data-podlove-input-status-for="<?php echo $this->field_id; ?>"></span>
		</div>
		<?php
    }

    public function hidden($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<div>
			<input type="hidden" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr($this->field_value); ?>" <?php echo $this->html_attributes; ?>>
		</div>
		<?php
    }

    public function password($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<div>
			<input type="password" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr($this->field_value); ?>" <?php echo $this->html_attributes; ?>>
		</div>
		<?php
    }

    public function text($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<div>
			<textarea name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html_attributes; ?>><?php echo $this->field_value; ?></textarea><span class="podlove-input-status" data-podlove-input-status-for="<?php echo $this->field_id; ?>"></span>
		</div>
		<?php
    }

    public function checkbox($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<input type="checkbox" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php if (in_array($this->field_value, [true, 1, 'on'])) { ?>checked="checked"<?php } ?> <?php echo $this->html_attributes; ?>>
		<input type="hidden" name="checkboxes[]" value="<?php echo esc_attr($this->object_key); ?>">
		<?php
    }

    public function select($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<select name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" <?php echo $this->html_attributes; ?>>
			<?php if (!isset($this->arguments['please_choose']) || $this->arguments['please_choose']) { ?>
				<option value=""><?php
                    if (isset($this->arguments['please_choose_text'])) {
                        echo $this->arguments['please_choose_text'];
                    } else {
                        echo __('Please choose ...', 'podlove-podcasting-plugin-for-wordpress');
                    } ?></option>
			<?php } ?>
			<?php foreach ($this->arguments['options'] as $key => $value) { ?>
				<?php
                if (is_array($value)) {
                    $attributes = $value['attributes'];
                    $value = $value['value'];
                } else {
                    $attributes = '';
                } ?>
				<option value="<?php echo esc_attr($key); ?>" <?php echo $attributes; ?> <?php if ($key == $this->field_value) { ?> selected="selected"<?php } ?>><?php echo $value; ?></option>
			<?php } ?>
		</select>
		<?php
    }

    public function multiselect($object_key, $arguments)
    {
        $arguments['ignore_values'] = true;
        $this->build_input_values($object_key, $arguments);

        foreach ($this->arguments['options'] as $key => $value) {
            if (isset($this->arguments['multi_values'][$key])) {
                $checked = $this->arguments['multi_values'][$key];
            } else {
                $checked = $this->arguments['default'];
            }

            $name = $this->field_name.'['.$key.']';

            // generate an id without braces by turning braces into underscores
            $id = $this->field_id.'_'.$key;
            $id = str_replace(['[', ']'], '_', $id);
            $id = str_replace('__', '_', $id);

            if (isset($this->arguments['multiselect_callback'])) {
                $callback = call_user_func($this->arguments['multiselect_callback'], $key);
            } else {
                $callback = '';
            }

            $html = function () use ($id, $name, $checked, $callback, $value) {
                ?>
				<div>
					<label for="<?php echo $id; ?>">
						<input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php if ($checked) { ?>checked="checked"<?php } ?> <?php echo $callback; ?>> <?php echo $value; ?>
					</label>
				</div>
				<?php
            };

            if (isset($this->arguments['around_each']) && is_callable($this->arguments['around_each'])) {
                $this->arguments['around_each']($html);
            } else {
                call_user_func($html);
            }
        }
    }

    public function radio($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments); ?>
		<?php foreach ($this->arguments['options'] as $key => $value) { ?>
			<input type="radio" id="<?php echo $this->field_id.'_'.$key; ?>" name="<?php echo $this->field_name; ?>" value="<?php echo esc_attr($key); ?>"<?php if ($key == $this->field_value) { ?> checked="checked"<?php } ?>>
			<label for="<?php echo $this->field_id.'_'.$key; ?>"><?php echo $value; ?></label>
		<?php } ?>
		<?php
    }

    public function image($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments);

        // determine image dimensions
        $img_html_attributes = '';

        if (isset($arguments['image_width'])) {
            $img_html_attributes .= ' width="'.$arguments['image_width'].'"';
        }

        if (isset($arguments['image_height'])) {
            $img_html_attributes .= ' height="'.$arguments['image_height'].'"';
        } ?>
		<div>
			<input type="text" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>" value="<?php echo esc_attr($this->field_value); ?>" <?php echo $this->html_attributes; ?>><span class="podlove-input-status" data-podlove-input-status-for="<?php echo $this->field_id; ?>"></span>
			<br>
			<img src="<?php echo $this->field_value; ?>" <?php echo $img_html_attributes; ?> />
		</div>
		<script type="text/javascript">
		(function($) {
			$("#<?php echo $this->field_id; ?>").on( 'change', function() {
				url = $(this).val();
				$(this).parent().find("img").attr("src", url);
			} );
		})(jQuery);
		</script>
		<?php
    }

    public function upload($object_key, $arguments)
    {
        $this->build_input_values($object_key, $arguments);
        wp_enqueue_media();

        $defaults = [
            'form_button_text' => __('Select', 'podlove-podcasting-plugin-for-wordpress'),
            'media_button_text' => __('Use Image', 'podlove-podcasting-plugin-for-wordpress'),
            'media_title' => __('Image', 'podlove-podcasting-plugin-for-wordpress'),
            'allow_gravatar' => false,
            'allow_multi_upload' => false
        ];
        $arguments = wp_parse_args($arguments, $defaults); ?>
		<div class="podlove-media-upload-wrap">
			<span>
				<input type="text" <?php echo $this->html_attributes; ?> value="<?php echo esc_attr($this->field_value); ?>" name="<?php echo $this->field_name; ?>" id="<?php echo $this->field_id; ?>">
				<a href="#" class="podlove-media-upload button"
					data-target="<?php echo $this->field_id; ?>"
					data-title="<?php echo $arguments['media_title']; ?>"
					data-type="image"
					data-button="<?php echo $arguments['media_button_text']; ?>"
					data-class="media-frame"
					data-frame="select"
					data-size="full"
					data-state="podlove_select_single_image"
					data-preview=".podlove_preview_pic"
					data-allow-gravatar="<?php echo $arguments['allow_gravatar']; ?>"
                    data-multiple="<?php echo $arguments['allow_multi_upload']; ?>"
					data-fetch="url"><?php echo $arguments['form_button_text']; ?></a>
			</span>
			<?php if (!isset($arguments['description']) || !$arguments['description']) { ?>
				<p>
					<span class="description"><?php echo __('Enter URL or select image from media library.', 'podlove-podcasting-plugin-for-wordpress'); ?></span>
				</p>
			<?php } ?>
			<div class="podlove_preview_pic"></div>
		</div>
		<?php
    }

    public function callback($object_key, $arguments)
    {
        call_user_func($arguments['callback']);
    }

    /**
     * Build nested form.
     *
     * @param object   $object   object that shall be modified via the form
     * @param array    $args     list of options, all optional
     *                           - hidden dictionary with hidden values
     * @param function $callback inner form
     */
    public function fields_for($object, $args, $callback)
    {
        // determine context
        $context = isset($args['context']) ? $this->context.'['.$args['context'].']'."[{$object->id}]" : $this->context;
        // build input elements
        call_user_func($callback, new \Podlove\Form\Input\Builder($object, $context));
    }

    /**
     * Generate values required to build input fields.
     *
     * @param string $object_key name of the model attribute
     * @param array  $arguments  input field options
     */
    private function build_input_values($object_key, $arguments)
    {
        $this->object_key = $object_key;
        $this->arguments = $arguments;

        $this->field_name = $this->get_field_name();

        // multiselect takes care of its values
        if (!isset($arguments['ignore_values']) || $arguments['ignore_values'] === false) {
            $this->field_value = $this->object->{$object_key};

            if ($this->field_value === null && isset($arguments['default']) && $arguments['default']) {
                $this->field_value = $arguments['default'];
            }
        }

        $this->field_id = $this->get_field_id();
        $this->html_attributes = $this->get_extra_html_attributes();
    }
}
