require 'rubygems'
require 'yaml'
require 'mechanize'

CONFIG = YAML::load_file(File.join(__dir__, 'config.yml'))

def base_url
	CONFIG['web']['base']
end

def wplogin(agent)
	page = agent.get(base_url + '/wp-admin')
	login_form = page.form('loginform')
	login_form.log = 'admin'
	login_form.pwd = 'admin'

	page = agent.submit(login_form, login_form.buttons.first)
	page.search("#wp-admin-bar-my-account a").text.should match(/Howdy, admin/)
	page
end
