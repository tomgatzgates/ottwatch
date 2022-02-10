class DevappController < ApplicationController
  def index
    relation = if params["before_id"]
      DevApp::Entry.where("id < ?", params["before_id"])
    else
      DevApp::Entry.all
    end

    @devapps = relation.order(id: :desc).limit(10)

    # binding.pry
  end

  def show
    @entry = DevApp::Entry.where(app_number: params[:app_number]).includes(:addresses, :documents).first
  end
end
