<?php

namespace Podlove\Template;

/**
 * Episode Template Wrapper.
 *
 * @templatetag image
 */
class Image extends Wrapper
{
    /**
     * @var Podlove\Model\Image
     */
    private $image;

    public function __construct(\Podlove\Model\Image $image)
    {
        $this->image = $image;
    }

    // /////////
    // Accessors
    // /////////

    public function __toString()
    {
        return $this->image->url();
    }

    /**
     * Get data-uri for resized image.
     *
     * **Parameters**
     *
     * see `url`
     *
     * **Examples**
     *
     * ```jinja
     * {{ image.dataUri }}               {# returns the unresized image data URI #}
     * {{ image.dataUri({width: 100}) }} {# returns resized image data URI #}
     * <img src="{{ image.dataUri }}" /> {# use it as img source #}
     * ```
     *
     * **Return**
     *
     * The return value is a complete data uri like `data:image/png;base64,iVB...ggg==`.
     *
     * @accessor
     *
     * @param mixed $args
     */
    public function dataUri($args = [])
    {
        $defaults = [
            'width' => null,
            'height' => null,
            'crop' => false,
        ];
        $args = wp_parse_args($args, $defaults);

        $file = $this->image
            ->setCrop((bool) $args['crop'])
            ->setWidth($args['width'])
            ->setHeight($args['height'])
            ->resized_file()
        ;

        if (!file_exists($file)) {
            $this->image->download_source();
            if ($args['width'] || $args['height']) {
                $this->image->generate_resized_copy();
            }
        }

        // fallback
        if (!file_exists($file)) {
            return $this->url($args);
        }

        $data = file_get_contents($file);
        $data64 = base64_encode($data);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($data);

        return 'data:'.$mime.';base64,'.$data64;
    }

    /**
     * Get URL for resized image.
     *
     * **Parameters**
     *
     * - width: Image width. Set width and leave height blank to keep the orinal aspect ratio.
     * - height: Image height. Set height and leave width blank to keep the orinal aspect ratio.
     * - crop: true or false. Crop image if given dimensions deviate from original aspect ratio. Default: false.
     *
     * **Examples**
     *
     * ```jinja
     * {{ image.url }}               {# returns the unresized image URL #}
     * {{ image.url({width: 100}) }} {# returns resized image URL #}
     * ```
     *
     * Note: It is not _guaranteed_ to get back the resized image. If it is
     * not ready yet, the source URL will be returned.
     *
     * @accessor
     *
     * @param mixed $args
     */
    public function url($args = [])
    {
        $defaults = [
            'width' => null,
            'height' => null,
            'crop' => false,
        ];
        $args = wp_parse_args($args, $defaults);

        return $this->image
            ->setCrop((bool) $args['crop'])
            ->setWidth($args['width'])
            ->setHeight($args['height'])
            ->url()
        ;
    }

    /**
     * Get HTML image tag for resized image.
     *
     * **Parameters**
     *
     * - width: Image width. Set width and leave height blank to keep the orinal aspect ratio.
     * - height: Image height. Set height and leave width blank to keep the orinal aspect ratio.
     * - crop: true or false. Crop image if given dimensions deviate from original aspect ratio. Default: false.
     * - id: Set image tag "id" attribute.
     * - class: Set image tag "class" attribute.
     * - style: Set image tag "style" attribute.
     * - alt: Set image tag "alt" attribute.
     * - title: Set image tag "title" attribute.
     *
     * **Examples**
     *
     * ```jinja
     * {{ image.html }}                       {# returns the unresized image tag #}
     * {{ image.html({width: 100}) }}         {# returns resized image tag #}
     * {{ image.html({title: "The Spark"}) }} {# returns image tag with custom title #}
     * ```
     *
     * Note: It is not _guaranteed_ to get back the resized image. If it is
     * not ready yet, the source URL will be returned.
     *
     * @accessor
     *
     * @param mixed $args
     */
    public function html($args = [])
    {
        $defaults = [
            'width' => null,
            'height' => null,
            'crop' => false,
            'id' => null,
            'class' => null,
            'style' => null,
            'alt' => null,
            'title' => null,
            'attributes' => [],
            'retina' => true,
        ];
        $args = wp_parse_args($args, $defaults);

        return $this->image
            ->setCrop((bool) $args['crop'])
            ->setRetina((bool) $args['retina'])
            ->setWidth($args['width'])
            ->setHeight($args['height'])
            ->image([
                'id' => $args['id'],
                'class' => $args['class'],
                'style' => $args['style'],
                'alt' => $args['alt'],
                'title' => $args['title'],
                'attributes' => $args['attributes'],
            ])
        ;
    }

    protected function getExtraFilterArgs()
    {
        return [$this->image];
    }
}
