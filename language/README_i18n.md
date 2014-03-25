To create a podlove.pot do:

cd ..
find . -iname "*.php" > /tmp/my_theme_file_list.txt
xgettext --from-code=utf-8 -d podlove  -f /tmp/my_theme_file_list.txt --keyword='__:1' --keyword='_n,1,2' --keyword='_e:1' -o language/podlove.pot


To create a podlove mo files do:
for file in `find . -name "*.po"` ; do msgfmt -o ${file/.po/.mo} $file ; done
