To create a podlove.pot do:

cd ..
find . -iname "*.php" > /tmp/my_theme_file_list.txt
xgettext --from-code=utf-8 -d podlove  -f /tmp/my_theme_file_list.txt --keyword=__ -o language/podlove.pot
