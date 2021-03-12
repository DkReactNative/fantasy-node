var DataTypes = require("sequelize").DataTypes;
var _admin_wallet = require("./admin_wallet");
var _bank_details = require("./bank_details");
var _banners = require("./banners");
var _blogs = require("./blogs");
var _category = require("./category");
var _commentaries = require("./commentaries");
var _contact_us = require("./contact_us");
var _contents = require("./contents");
var _contest = require("./contest");
var _coupon_codes = require("./coupon_codes");
var _croninfo = require("./croninfo");
var _custom_breakup = require("./custom_breakup");
var _custom_breakupmain = require("./custom_breakupmain");
var _deposit_amount_logs = require("./deposit_amount_logs");
var _device_info = require("./device_info");
var _dream_teams = require("./dream_teams");
var _earnings = require("./earnings");
var _email_templates = require("./email_templates");
var _gst = require("./gst");
var _invites = require("./invites");
var _join_contest_detail_results = require("./join_contest_detail_results");
var _join_contest_details = require("./join_contest_details");
var _league_contest_details = require("./league_contest_details");
var _league_contests = require("./league_contests");
var _live_score = require("./live_score");
var _match_contest = require("./match_contest");
var _mst_teams = require("./mst_teams");
var _notifications = require("./notifications");
var _pen_aadhar_card = require("./pen_aadhar_card");
var _player_record = require("./player_record");
var _player_team_contest_results = require("./player_team_contest_results");
var _player_team_contests = require("./player_team_contests");
var _player_team_detail_results = require("./player_team_detail_results");
var _player_team_details = require("./player_team_details");
var _player_team_results = require("./player_team_results");
var _player_teams = require("./player_teams");
var _point_system = require("./point_system");
var _points_breakup = require("./points_breakup");
var _received_notifications = require("./received_notifications");
var _referal_amount_details = require("./referal_amount_details");
var _referal_code_details = require("./referal_code_details");
var _series = require("./series");
var _series_players = require("./series_players");
var _series_squad = require("./series_squad");
var _settings = require("./settings");
var _subscribe_users = require("./subscribe_users");
var _tds_details = require("./tds_details");
var _transactions = require("./transactions");
var _user_avetars = require("./user_avetars");
var _user_contest_breakup = require("./user_contest_breakup");
var _user_contest_rewards = require("./user_contest_rewards");
var _user_contests = require("./user_contests");
var _user_coupon_codes = require("./user_coupon_codes");
var _users = require("./users");
var _withdraw_requests = require("./withdraw_requests");
var _xx_match = require("./xx_match");
var _xxx = require("./xxx");

function initModels(sequelize) {
  var admin_wallet = _admin_wallet(sequelize, DataTypes);
  var bank_details = _bank_details(sequelize, DataTypes);
  var banners = _banners(sequelize, DataTypes);
  var blogs = _blogs(sequelize, DataTypes);
  var category = _category(sequelize, DataTypes);
  var commentaries = _commentaries(sequelize, DataTypes);
  var contact_us = _contact_us(sequelize, DataTypes);
  var contents = _contents(sequelize, DataTypes);
  var contest = _contest(sequelize, DataTypes);
  var coupon_codes = _coupon_codes(sequelize, DataTypes);
  var croninfo = _croninfo(sequelize, DataTypes);
  var custom_breakup = _custom_breakup(sequelize, DataTypes);
  var custom_breakupmain = _custom_breakupmain(sequelize, DataTypes);
  var deposit_amount_logs = _deposit_amount_logs(sequelize, DataTypes);
  var device_info = _device_info(sequelize, DataTypes);
  var dream_teams = _dream_teams(sequelize, DataTypes);
  var earnings = _earnings(sequelize, DataTypes);
  var email_templates = _email_templates(sequelize, DataTypes);
  var gst = _gst(sequelize, DataTypes);
  var invites = _invites(sequelize, DataTypes);
  var join_contest_detail_results = _join_contest_detail_results(sequelize, DataTypes);
  var join_contest_details = _join_contest_details(sequelize, DataTypes);
  var league_contest_details = _league_contest_details(sequelize, DataTypes);
  var league_contests = _league_contests(sequelize, DataTypes);
  var live_score = _live_score(sequelize, DataTypes);
  var match_contest = _match_contest(sequelize, DataTypes);
  var mst_teams = _mst_teams(sequelize, DataTypes);
  var notifications = _notifications(sequelize, DataTypes);
  var pen_aadhar_card = _pen_aadhar_card(sequelize, DataTypes);
  var player_record = _player_record(sequelize, DataTypes);
  var player_team_contest_results = _player_team_contest_results(sequelize, DataTypes);
  var player_team_contests = _player_team_contests(sequelize, DataTypes);
  var player_team_detail_results = _player_team_detail_results(sequelize, DataTypes);
  var player_team_details = _player_team_details(sequelize, DataTypes);
  var player_team_results = _player_team_results(sequelize, DataTypes);
  var player_teams = _player_teams(sequelize, DataTypes);
  var point_system = _point_system(sequelize, DataTypes);
  var points_breakup = _points_breakup(sequelize, DataTypes);
  var received_notifications = _received_notifications(sequelize, DataTypes);
  var referal_amount_details = _referal_amount_details(sequelize, DataTypes);
  var referal_code_details = _referal_code_details(sequelize, DataTypes);
  var series = _series(sequelize, DataTypes);
  var series_players = _series_players(sequelize, DataTypes);
  var series_squad = _series_squad(sequelize, DataTypes);
  var settings = _settings(sequelize, DataTypes);
  var subscribe_users = _subscribe_users(sequelize, DataTypes);
  var tds_details = _tds_details(sequelize, DataTypes);
  var transactions = _transactions(sequelize, DataTypes);
  var user_avetars = _user_avetars(sequelize, DataTypes);
  var user_contest_breakup = _user_contest_breakup(sequelize, DataTypes);
  var user_contest_rewards = _user_contest_rewards(sequelize, DataTypes);
  var user_contests = _user_contests(sequelize, DataTypes);
  var user_coupon_codes = _user_coupon_codes(sequelize, DataTypes);
  var users = _users(sequelize, DataTypes);
  var withdraw_requests = _withdraw_requests(sequelize, DataTypes);
  var xx_match = _xx_match(sequelize, DataTypes);
  var xxx = _xxx(sequelize, DataTypes);


  return {
    admin_wallet,
    bank_details,
    banners,
    blogs,
    category,
    commentaries,
    contact_us,
    contents,
    contest,
    coupon_codes,
    croninfo,
    custom_breakup,
    custom_breakupmain,
    deposit_amount_logs,
    device_info,
    dream_teams,
    earnings,
    email_templates,
    gst,
    invites,
    join_contest_detail_results,
    join_contest_details,
    league_contest_details,
    league_contests,
    live_score,
    match_contest,
    mst_teams,
    notifications,
    pen_aadhar_card,
    player_record,
    player_team_contest_results,
    player_team_contests,
    player_team_detail_results,
    player_team_details,
    player_team_results,
    player_teams,
    point_system,
    points_breakup,
    received_notifications,
    referal_amount_details,
    referal_code_details,
    series,
    series_players,
    series_squad,
    settings,
    subscribe_users,
    tds_details,
    transactions,
    user_avetars,
    user_contest_breakup,
    user_contest_rewards,
    user_contests,
    user_coupon_codes,
    users,
    withdraw_requests,
    xx_match,
    xxx,
  };
}
module.exports = initModels;
module.exports.initModels = initModels;
module.exports.default = initModels;
