require_relative "../test_helper"

describe "Basic Setup Workflow" do

	before(:all) do
	  system "gunzip -c test/fixtures/clean.sql.gz > test/fixtures/dump.sql"
	end

	after(:all) do
		system "rm test/fixtures/dump.sql"
	end

	before(:each) do
		# prepare db
		ENV['MYSQL_PWD'] = CONFIG['database']['password']
		system "mysql -u #{CONFIG['database']['user']} #{CONFIG['database']['database']} < test/fixtures/dump.sql"

		# prepare mechanize and login
	  @agent = Mechanize.new
	  @page = wplogin(@agent)
	end

  it "works" do
  	# podcast settings
  	@page = @agent.get(base_url + '/wp-admin/admin.php?page=podlove_settings_podcast_handle')
  	@page = @page.form_with(action: 'options.php') do |f|
  		f['podlove_podcast[title]'] = 'Test Podcast'
  		f['podlove_podcast[subtitle]'] = 'the one and only'
  		f['podlove_podcast[media_file_base_uri]'] = 'http://satoripress.com/wp-content/ppp/'
  	end.submit

  	# assets
  	@page = @agent.get(base_url + '/wp-admin/admin.php?page=podlove_episode_assets_settings_handle&action=new')
  	@page = @page.form_with(action: 'admin.php?page=podlove_episode_assets_settings_handle') do |f|
  		f['podlove_episode_asset[file_type_id]'] = 1 # mp3 audio
  		f['podlove_episode_asset[title]'] = 'MP3 Audio'
  	end.submit
  	@page.search("table.episode_assets tbody tr td.title").text.should match(/MP3 Audio/)

  	# feeds
  	@page = @agent.get(base_url + '/wp-admin/admin.php?page=podlove_feeds_settings_handle&action=new')
  	@page = @page.form_with(action: 'admin.php?page=podlove_feeds_settings_handle') do |f|
  		f.field_with(id: 'podlove_feed_episode_asset_id').options[1].select
  		f['podlove_feed[name]'] = 'MP3 Audio Feed'
  		f['podlove_feed[slug]'] = 'mp3'
  	end.submit
  	@page.search("table.feeds tbody tr:first td.media").text.should match(/MP3 Audio \(mp3\)/)

  	# create episode
  	@page = @agent.get(base_url + '/wp-admin/post-new.php?post_type=podcast')

  	form = @page.form_with(action: 'post.php') do |f|
  		f['post_title'] = 'PPP001 The Title'
  		f['_podlove_meta[slug]'] = 'ppp001'
  		f.checkboxes.first.check
  	end
  	@page = @agent.submit(form, form.buttons.keep_if { |b| b.name == 'publish' }.first)

  	# view episode
  	@page = @agent.get(base_url + '/?podcast=ppp001-the-title')
  	@page.search('[text()*="PPP001 The Title"]').length.should > 0

  	# view feed
  	@page = @agent.get(base_url + '/?feed=mp3')
  	@page.search('enclosure')[0].attr('url').should == 'http://satoripress.com/wp-content/ppp/ppp001.mp3'
  end
end