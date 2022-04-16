xml.instruct! :xml, :version => "1.0"
xml.rss :version => "2.0" do
  xml.channel do
    xml.title "OttWatch Announcements"
    xml.description "Latest announcements"
    xml.link root_url

    @announcements.each do |a|
      xml.item do
        xml.title a.message
        xml.description "#{a.link_to_context}: #{a.reference.desc}"
        xml.pubDate a.created_at.to_fs(:rfc822)
        xml.link a.link_to_reference
        xml.guid a.id
      end
    end
  end
end