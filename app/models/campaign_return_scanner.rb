require 'rmagick'

include Magick

class CampaignReturnScanner
  def self.scan(candidate:, pdf_file:)
    cr = nil
    CampaignReturn.transaction do
      cr = candidate.campaign_returns.create!
      cr.pdf.attach(io: File.open(pdf_file), filename: pdf_file, content_type: 'application/pdf')

      dir = Dir.mktmpdir("campaign_return_scanner")
      unless system("pdftoppm", "-png", pdf_file.to_path, File.join(dir, "page"))
        raise StandardError.new("pdftoppm failed")
      end

      Dir["#{dir}/*"].each_with_index do |p, i|
        cr_page = cr.campaign_return_pages.create!
        cr_page.img.attach(io: File.open(p), filename: "page_#{i+1}.png", content_type: '	image/x-png')
      end
    end
    cr
  end
end
