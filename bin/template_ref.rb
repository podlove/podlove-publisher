require "erb"
require "json"

def templateRefClasses
	classes = %w{podcast episode chapter feed asset file duration file_type contributor contributor_group service license flattr datetime}
	classes.map { |klass| JSON.parse(IO.read("doc/data/template/#{klass}.json")) }
end

def renderDescription(s)
	if s
		s.gsub!(/^(\s+)```/, "\n```")
		s.gsub!(/```(\w+)?([^`]+)```/) { |m| "```" + $1.to_s + "\n{% raw %}" + $2.to_s + "{% endraw %}\n```" }
		s.gsub!(/[^`]`([^`]+)`/) { |m| "`{% raw %}" + $1.to_s + "{% endraw %}`" }
		s = "{% capture tmp %}" + s + "{% endcapture %}\n{{ tmp | markdownify }}"
	end

	s
end

renderer = ERB.new(File.read("bin/template_ref.erb"))
puts output = renderer.result()