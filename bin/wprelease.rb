require "yaml"

class Object

	def blank?
	  respond_to?(:empty?) ? empty? : !self
	end

	def present?
	  !blank?
	end

end

class String

	def is_plugin_file?
		open(self).grep(/plugin name/i).length > 0
	end

end

# TODO: write all commit messages since last release in svn message
class Wprelease
	
	attr_accessor :config
	attr_accessor :plugin_file, :plugin_dir, :svn_dir, :repo_slug
	attr_accessor :version

	def initialize

		begin
			@config = YAML.load_file("wprelease.yml")
		rescue Exception => e
			@config = {}			
		end

		self.determine_plugin_file

		@repo_slug = @config["repository"] || open(@plugin_file).grep(/Plugin Name:/i).first.split(":").last.strip.downcase.split.join("-").gsub(%r{[-]+},"-")

		@plugin_dir = File.basename(Dir.getwd)
		@absolute_plugin_dir = Dir.pwd
		@version = open(@plugin_file).grep(/Version:/i).first.split(":").last.strip
		@svn_dir = @config["svn_dir"] || File.dirname(Dir.pwd) + "/" + @plugin_dir + "-svn"

		if File.directory? @svn_dir
			# update svn directory before committing new changes.
			# this might solve a recurring issue when deleting files/directories
			Dir.chdir "#{@svn_dir}"
			system "svn update"
			Dir.chdir @absolute_plugin_dir
		else
			initial_checkout
		end

		die("Found no plugin file") unless @plugin_file.present?
		die("Found no version number") unless @version.present?
		die("Didn't find expected svn directory: #{@svn_dir}") unless File.directory?(@svn_dir)

		puts "creating git tag ..."
		system "git tag -a #{@version} -m 'version #{@version}'"
		system "git push --tags"

		puts "rsync files ..."
		# system "rsync --recursive --quiet --delete --exclude=wprelease.yml --exclude=.git --exclude=.wordpress_release --exclude=.gitmodules --exclude=.tags --exclude=.tags_sorted_by_file --exclude=podlove.sublime-workspace --exclude=podlove.sublime-project . #{@svn_dir}/trunk"
		# system "grunt"
		system "rsync ./ #{@svn_dir}/trunk --recursive --delete --delete-excluded --exclude=.git --exclude=#{@svn_dir} --exclude=.gitmodules --exclude=.tags --exclude=tags --exclude=.ctags --exclude=.tags_sorted_by_file --exclude=wprelease.yml --exclude=podlove.sublime-workspace --exclude=podlove.sublime-project --exclude=lib/modules/podlove_web_player/player/podlove-web-player/libs --exclude=vendor/bin --exclude=vendor/phpunit --exclude=node_modules --exclude=Gruntfile.js --exclude=phpunit.xml --exclude=test --exclude=Rakefile"

		puts "committing changes to svn ..."

		system "git log --pretty=oneline --abbrev-commit `git describe --abbrev=0 --tags`..HEAD > /tmp/svn_msg"
		
		Dir.chdir "#{@svn_dir}/trunk"

		`svn status | grep '?'`.each_line do |line|
			untracked_file = line.split[1]
			puts "Add untracked file: #{untracked_file}"
			system "svn add #{untracked_file}"
		end

		deleted_files = []
		`svn status | grep '!'`.each_line do |line|
			deleted_file = line.split[1]
			puts "Remove deleted file: #{deleted_file}"
			system "svn rm #{deleted_file}"
			deleted_files << deleted_file
		end
		if deleted_files.length
			system("svn commit -m 'remove deleted files' " + deleted_files.join(" ") )
		end

		if system("svn commit -m 'release'")
			puts "create svn tag ..."
			system "svn copy -m \"version #{@version}\" http://plugins.svn.wordpress.org/#{@repo_slug}/trunk http://plugins.svn.wordpress.org/#{@repo_slug}/tags/#{@version}"
		else
			puts "abort"
		end
	end

	def determine_plugin_file
		Dir['*.php'].each do |entry|
			@plugin_file = entry; break if entry.is_plugin_file?
		end
	end

	def initial_checkout
		puts "> svn woring directory does not exist. checking out:"
		puts "> create dir #{@svn_dir}"
		Dir.mkdir @svn_dir
		puts "> cd into #{@svn_dir}"
		Dir.chdir @svn_dir

		puts "> checkout svn repo ..."
		system "svn co http://plugins.svn.wordpress.org/#{repo_slug} ."

		# get back to plugin dir
		Dir.chdir @absolute_plugin_dir
	end

	def die(message)
		puts message
		exit
	end

end

release = Wprelease.new
