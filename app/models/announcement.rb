class Announcement < ApplicationRecord
  belongs_to :reference, polymorphic: true

  def reference_context
    if reference.is_a?(Consultation)
      reference.title
    end
    if reference.is_a?(DevApp::Entry)
      if addr = reference.addresses.first
        parts = [addr.road_number, addr.road_name, addr.road_type, addr.direction].reject{|c| c == ""}
        return nil if parts.count < 2
        return parts.join(" ")
      end
    end
    return "#{reference.start_time.in_time_zone("America/New_York").strftime("%b %d %H:%M")}" if reference.is_a?(Meeting)
    if reference.is_a?(LobbyingUndertaking)
      issue = reference.issue || ""
      "#{reference.lobbyist_name} (#{reference.lobbyist_position}): #{issue.split(" ").first(10).join(" ")} ..."
    end
  end

  def reference_link
    return reference.full_href if reference.is_a?(Consultation)

    # TODO: this should move to a helper that can be used in the UI as well
    url = if Rails.env.production?
      "https://ottwatch.ca"
    else
      "http://localhost:33000"
    end

    return "#{url}/devapp/#{reference.app_number}" if reference.is_a?(DevApp::Entry)
    if reference.is_a?(Meeting)
      return "#{url}/meeting/#{reference.reference_id}" if reference.reference_id
      return "#{url}/meeting/#{reference.reference_guid}" if reference.reference_guid
    end
    return "#{url}/lobbying/#{reference.id}" if reference.is_a?(LobbyingUndertaking)
  end
end
