desc "update all submodules"
task :update_submodules do
	`git submodule update --recursive`
end

task :cloc do
	puts `cloc --by-file-by-lang --exclude-dir=node_modules,vendor,lib/modules/podlove_web_player/player,.git,js/admin/codemirror,js/admin/jquery-ui --not-match-f=".*.min.(js|css)|cornify.js" *`
end	
