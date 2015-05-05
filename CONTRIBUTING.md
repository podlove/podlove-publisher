Thanks for reading our contribution guidelines!

* [Report a Bug](#report-bug)
* [Ask for Support](#request-support)
* [Help / Donate](#donate)

<a name=“report-bug”></a>
# Report a Bug

Something is not working?
Please follow the steps below to help us isolate the cause of error.

### Disable Podlove Cache

While testing, disable our internal cache. Put the following at the end of `wp-config.php`

```php
# wp-config.php
define('PODLOVE_TEMPLATE_CACHE', false);
```

### Disable other Caches

If you are using a caching plugin, please deactivate it. Examples for such plugins are:

- W3 Total Cache
- WP Super Cache
- Quick Cache

### Does it work when you use a default theme (like “twentyfifteen”)?

Sometimes themes change default WordPress behavior that breaks plugins. By testing your setup with a default theme, we can make sure it's not the themes fault.

### Does it work when you disable all plugins except the Publisher?

Just like the theme, other plugins might interfere with how the Publisher works.

### Now What?

You followed the steps above and the error still persists?
Create a [GitHub Issue](https://github.com/podlove/podlove-publisher/issues) if you haven't done so already, paste the output from your `Podlove ➜ Support` menu and mention that you have followed the steps above.

Thank you!

<a name=“request-support”></a>
# Ask for Support

If you are looking for professional support, head over to publisher.podlove.org/support/.

We have a community forum for questions, answers and feature discussions at https://community.podlove.org.

Please check if your questions are answered in our growing documentation site http://docs.podlove.org. If you still have open questions, feel free to open a support issue.

<a name=“donate”></a>
# Donate

We are happy about every donation. Please visit http://podlove.org/donations/ for details.