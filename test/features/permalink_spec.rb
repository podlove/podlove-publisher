require_relative "../test_helper"

describe "Permalink Settings" do

	before(:all) do
	  system "gunzip -c test/fixtures/2episodes.sql.gz > test/fixtures/dump.sql"
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

  it "NonPretty" do
    @page = @agent.get(base_url + '/wp-admin/options-permalink.php')
    @page.form_with(action: 'options-permalink.php') do |f|
      f.radiobuttons.last.check
      f.permalink_structure = ''
    end.submit

    verifyPageContainsText      '/?p=1', "Hello world"
    verifyPageContainsText      '/?page_id=2', "Sample Page"
    verifyPageContainsText      '/?podcast=ppp001-the-title', "PPP001 The Title"
    verifyPageContainsEnclosure '/?feed=mp3', "ppp001.mp3"
  end

  it "PostnamePretty" do
    @page = @agent.get(base_url + '/wp-admin/options-permalink.php')
    @page.form_with(action: 'options-permalink.php') do |f|
      f.radiobuttons.last.check
      f.permalink_structure = '/%postname%/'
    end.submit

    verifyPageContainsText      '/hello-world/', 'Hello world!'
    verifyPageContainsText      '/sample-page/', 'Sample Page'
    verifyPageContainsText      '/ppp001-the-title/', 'PPP001 The Title'
    verifyPageContainsEnclosure '/feed/mp3', 'ppp001.mp3'
  end

  it "PostnamePrettyAndEpisodeCustom" do
    @page = @agent.get(base_url + '/wp-admin/options-permalink.php')
    @page.form_with(action: 'options-permalink.php') do |f|
      f.radiobuttons.last.check
      f.permalink_structure = '/%postname%/'
    end.submit

    @page = @agent.get(base_url + '/wp-admin/admin.php?page=podlove_settings_settings_handle')
    @page.form_with(action: 'options.php') do |f|
      f.checkbox_with('podlove_website[use_post_permastruct]').uncheck
      f['podlove_website[custom_episode_slug]'] = '/episode/%podcast%/'
    end.submit

    verifyPageContainsText      '/hello-world/', 'Hello world!'
    verifyPageContainsText      '/sample-page/', 'Sample Page'
    verifyPageContainsText      '/episode/ppp001-the-title/', 'PPP001 The Title'
    verifyPageContainsEnclosure '/feed/mp3', 'ppp001.mp3' 
  end

  def verifyPageContainsText(url, text)
    @page = @agent.get(base_url + url)
    @page.search('[text()*="' + text + '"]').length.should > 0
  end

  def verifyPageContainsEnclosure(url, enclosure_url)
    @page = @agent.get(base_url + url)
    @page.search('enclosure [url*="' + enclosure_url + '"]').length.should > 0
  end

end