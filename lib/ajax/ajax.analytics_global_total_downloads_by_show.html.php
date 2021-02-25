<?php if (count($downloads) === 0) {
    echo 'no data';
} else { ?>
<table style="margin-left: 7px;" border="0" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th style="text-align: left; padding: 0 0 0.25rem 0.25rem"><?php _e('Show', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
            <th style="text-align: left; padding: 0 0 0.25rem 0.25rem"><?php _e('Downloads', 'podlove-podcasting-plugin-for-wordpress'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($downloads as $row) { ?>
            <?php ++$i; ?>
            <tr style="<?php echo ($i % 2 == 0) ? 'background-color: rgba(249, 250, 251, 1);' : ''; ?>">
                <td style="padding: 0.25rem 1rem 0.25rem 0.25rem;"><?php echo $row['show_name'] ?? __('Without Assigned Show', 'podlove-podcasting-plugin-for-wordpress'); ?></td>
                <td style="padding: 0.25rem 1rem 0.25rem 0.25rem;"><?php echo number_format_i18n($row['downloads']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<?php
}
