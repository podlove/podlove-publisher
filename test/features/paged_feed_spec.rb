require_relative "../test_helper"

describe "Paged Feed" do

	ATOM_XMLNS = 'http://www.w3.org/2005/Atom'

	before(:all) do
	  system "gunzip -c test/fixtures/cre.sql.gz > test/fixtures/dump.sql"
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

  it "References next, prev, first and last page" do
    @page = @agent.get(base_url + '/?feed=mp3&paged=2&redirect=no')
    @page.search("//channel/atom:link[@rel='self']", atom: ATOM_XMLNS).length.should == 1
    @page.search("//channel/atom:link[@rel='next']", atom: ATOM_XMLNS).length.should == 1
    @page.search("//channel/atom:link[@rel='prev']", atom: ATOM_XMLNS).length.should == 1
    @page.search("//channel/atom:link[@rel='first']", atom: ATOM_XMLNS).length.should == 1
    @page.search("//channel/atom:link[@rel='last']", atom: ATOM_XMLNS).length.should == 1
  end

  it "has different items on different pages" do
	@page2 = @agent.get(base_url + '/?feed=mp3&paged=2&redirect=no')
	@page3 = @agent.get(base_url + '/?feed=mp3&paged=3&redirect=no')

	@page2.at("//item[1]/title").content.should_not == @page3.at("//item[1]/title").content
  end

end