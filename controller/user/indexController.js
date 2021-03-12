var sequelize = require("../../db/index");
const { SeriesSquad, Series, MatchContest, MstTeams, PlayerTeams, Users, PlayerTeamContests, Contest, CustomBreakupmain, CustomBreakup, Category } = sequelize.models;
const { Op } = require("sequelize");
const _ = require("lodash");
const constants = require("../../constant/constants");
const {
  last,
  correctResponse,
  errorResponse,
  formatedDate,
  addDate,
} = require("../../helper/common");
const { floor, round } = require("lodash");


const getMatchList = async function (req, res) {
  let status = false;
  let message = null;

  let currentDate = formatedDate(
    new Date("2020-08-21T23:42:35.000Z"),
    "YYYY-MM-DD"
  );
  let oneMonthDate = addDate(new Date(), 10, "days", "YYYY-MM-DD");
  let currentTime = formatedDate(new Date(), "HH:MM");

  // set server Time Start
  let serverTimeZone = "UTC";
  let timeZone = "UTC";
  let currentDatTime = formatedDate(new Date(), "YYYY-MM-DD HH:MM:SS");
  let time = new Date();
  let serverTime = new Date();
  // set server Time End

  let decoded = req.body
  console.log(decoded)
  if (decoded.user_id) {
    console.log(currentDate);

    let filter = {
      localteam: { [Op.ne]: "TBA" },
      visitorteam: { [Op.ne]: "TBA" },
      series_id: { [Op.ne]: 3152 },
      [Op.or]: [
        {
          date: { [Op.gt]: currentDate + "" },
          time: { [Op.gte]: currentTime + "" },
        },
        {
          date: { [Op.between]: [currentDate + "", oneMonthDate + ""] },
        },
      ],
      status: 1,
    };

    let data = await SeriesSquad.findAll({
      include: [
        {
          model: Series,
          required: true,
          // attributes: ["id"],
          where: { status: 1 },
        },
        {
          model: MstTeams,
          // required: true,
          as: "VisitorMstTeams",
        },
        {
          model: MstTeams,
          // attributes: ["id"],
          // required: true,
          as: "LocalMstTeams",
        },
      ],
      where: filter,
      // raw: true,
      group: ["match_id"],
      order: ['date', 'time']
    });

    let matchContestArray = await MatchContest.count({
      group: ["match_id"],
      raw: true,
    });

    let matchContestObject = {};

    matchContestArray.map((ele) => {
      matchContestObject[ele.match_id] = ele.count;
    });

    //console.log("matchContest ------------>", data);

    data = data.map((ele) => {
      let obj = {};
      obj.total_contest = matchContestObject[ele.id]
        ? matchContestObject[ele.id]
        : 0;
      obj["is_lineup"] = ele.is_lineup;
      obj["series_id"] = ele.series_id;
      obj.mega_prize = ele.mega_prize ? ele.mega_prize : 0;
      obj.match_id = ele.match_id;
      obj.guru_url = ele.guru_url ? guru_url : "";
      obj["series_name"] = ele.series
        ? ele.series.short_name
          ? ele.series.short_name
          : ele.series.name.replace("Cricket ", "")
        : "";
      obj["local_team_id"] = ele.localteam_id;
      // console.log(ele)
      obj["local_team_name"] =
        ele.LocalMstTeams && ele.LocalMstTeams.team_short_name
          ? ele.LocalMstTeams.team_short_name
          : ele.localteam;
      obj["local_team_flag"] =
        ele.LocalMstTeams && ele.LocalMstTeams.flag
          ? process.env.FLAGIMAGEURL + ele.LocalMstTeams.flag
          : "";
      obj["visitor_team_id"] = ele.visitorteam_id;
      obj["visitor_team_name"] =
        ele.VisitorMstTeams && ele.VisitorMstTeams.team_short_name
          ? ele.VisitorMstTeams.team_short_name
          : ele.localteam;
      obj["visitor_team_flag"] =
        ele.VisitorMstTeams && ele.VisitorMstTeams.flag
          ? process.env.FLAGIMAGEURL + ele.VisitorMstTeams.flag
          : "";
      obj["star_date"] = new Date(ele.date);
      obj["star_time"] = ele.time;
      obj["server_time"] = new Date();
      return obj;
    });
    let response = {};
    response.upcoming_match = data;
    response.live_match = [];
    response.completed_match = [];
    response.version_code = req.body.version;
    response.apk_url = "";
    response.update_type = 1;
    response.update_text =
      "<ul><li>&nbsp;Introduced Leaderboard</li><li>&nbsp;Cash Back Offer</li><li>&nbsp;Bugs fixes and enhancements</li></ul>";
    response.popup_image = "";
    response.popup_on = 1;
    correctResponse(res, 200, message, response, true);
  }
  else {
    message = "You are not authenticated user."
    correctResponse(res, 400, message, {}, false);
  }
};

const contestpageapi = async function (req, res) {
  try {
    let status = false;
    let message = null;
    let data = [];
    let data1 = {}
    let data_row = [];
    let decoded = req.body
    console.log(decoded)
    if (decoded.user_id) {
      if (decoded['match_id']) {

        let trump_mode = decoded['trump_mode'] ? 1 : 0;

        let myTeams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'], match_id: decoded['match_id']
          },
          raw: true
        })
        console.log("myTeams ---------->", myTeams)


        let my_nteams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
            trump_mode: 0
          },
          raw: true
        })

        console.log("my_nteams ---------->", my_nteams)


        let my_tteams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
            trump_mode: 1
          },
          raw: true
        })
        console.log("my_tteams ---------->", my_tteams)

        let myContest = await PlayerTeamContests.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
          },
          raw: true,
          group: ["contest_id"]
        })



        let totalBalance, cashBalance, winngsAmount, bonus = 0;

        let users = await Users.findOne({
          attributes: ["id", "cash_balance", "winning_balance", "bonus_amount"],
          where: {
            id: decoded['user_id']
          },
          raw: true
        })

        console.log(users)

        if (users) {
          cashBalance = users.cash_balance;
          winngsAmount = users.winning_balance;
          bonus = users.bonus_amount;
          totalBalance = cashBalance + winngsAmount + bonus;
        }

        data1.totalBalance = round(totalBalance, 2);

        data1.my_teams = myTeams;
        data1.my_nteams = my_nteams;
        data1.my_tteams = my_tteams;
        data1.my_contests = myContest.length || 0;
        status = true;
      } else {
        message = "Match id is empty."
      }
    } else {
      message = "You are not authenticated user."
    }
    response_data = { 'status': status, 'tokenexpire': 0, 'message': message, 'data': data1 }
    res.json(response_data)
  }
  catch (err) {
    let response_data = { 'status': false, 'tokenexpire': 0, 'message': err.message || "Something went wrong", 'data': {} }
    res.json(response_data)
  }

};

const contestList = async function (req, res) {
  try {
    let status = false;
    let message = null;
    let data = {};
    let data1 = {};
    let data_row = [];
    let decoded = req.body

    if (decoded) {
      if (decoded['match_id']) {
        let trump_mode = decoded['trump_mode'] ? 1 : 0;

        let result = await MatchContest.findAll({
          include: [
            {
              model: SeriesSquad,
              required: true,
              where: {
                match_id: decoded.match_id
              },
              attributes: ["match_id",]
            },
            {
              model: Contest,
              required: true,
              where: {
                status: 1,
                trump_mode: trump_mode
              },
              include: [
                {
                  model: Category,
                  required: false,
                  attributes: ['id', 'image', 'category_name', 'description', 'category_color'],
                },
                {
                  model: CustomBreakup,
                  required: false,
                  attributes: ['id', 'contest_id', 'name', 'start', 'end', 'percentage', 'price']
                }
              ],
              attributes: ['id', 'confirmed_winning', 'contest_size', 'entry_fee', 'winning_amount', 'category_id', 'multiple_team', 'max_team_user', 'trump_mode', 'usable_bonus_percentage', 'is_adjustable']
            },
          ],
          where: {
            is_full: 0,
          },
          // order: [['contest.category.sequence', 'ASC'], ['contest.winning_amount', 'DESC']]
        })

        result = JSON.parse(JSON.stringify(result))

        let teamsJoinedContestWiseArray = await PlayerTeamContests.count({
          group: ["contest_id"],
          raw: true,
          where: {
            match_id: decoded.match_id
          }
        });

        let teamsJoinedContestWise = {};
        teamsJoinedContestWiseArray.map(ele => {
          teamsJoinedContestWise[ele.contest_id] = ele.count
        })

        let isJoined = await PlayerTeamContests.findAll({
          attributes: ["player_team_id", "contest_id"],
          where: {
            match_id: decoded.match_id,
            user_id: decoded.user_id
          },
          raw: true,
        })

        let myContestTeamIds = {}
        if (isJoined.length > 0) {
          isJoined.map(ele => {
            myContestTeamIds[ele.contest_id] = ele.player_team_id
          })
        }

        let counter = 0;
        let categoryArray = {}
        var contest = {};
        if (result && result.length > 0) {
          result.reduce(async (previousPromise, ele, contestKey) => {
            await previousPromise;
            let categoryInfo = ele.contest && ele.contest.category ? ele.contest.category : "";
            let categoryId = categoryInfo['id'];
            if (!categoryArray[categoryId]) {
              ontest = {}
              categoryArray[categoryId] = counter;
              let filePath = process.env.CATEGORYIMAGE
              let categoryImage = '';
              if (categoryInfo['image']) {
                categoryImage = filePath + categoryInfo['image'];
              }

              if (!data[counter]) {
                data[counter] = {}
              }

              data[counter]['id'] = categoryInfo['id'] ? categoryInfo['id'] : '';
              data[counter]['category_title'] = categoryInfo['category_name'] ? categoryInfo['category_name'] : '';
              data[counter]['category_desc'] = categoryInfo['description'] ? categoryInfo['description'] : '';
              data[counter]['category_image'] = categoryImage;
              data[counter]['category_color'] = categoryInfo['category_color'] ? categoryInfo['category_color'] : '#18D0F5';
              counter++;
            }

            let contestInfo = ele.contest;

            let customBreakup = contestInfo.custom_breakup;
            if (customBreakup && customBreakup.end) {
              let toalWinner = customBreakup.end;
            } else {
              toalWinner = customBreakup ? customBreakup.start : 0;
            }

            // find team that other users joined
            let teamsJoined = teamsJoinedContestWise[contestInfo.id] ? teamsJoinedContestWise[contestInfo.id] : 0;


            let myTeamIds = [];
            if (myContestTeamIds) {
              myTeamIds = myContestTeamIds[contestInfo.id] ? myContestTeamIds[contestInfo.id] : [];
            }

            var customPrice = {};
            var first_prize = '';
            console.log("contestInfo.custom_breakup", contestInfo.custom_breakups)
            if (contestInfo.custom_breakups && contestInfo.custom_breakups.length > 0) {
              contestInfo.custom_breakups.map((customBreakup, key) => {
                if (!customPrice[key]) {
                  customPrice[key] = {}
                }
                if (customBreakup.start == customBreakup.end) {
                  customPrice[key]['rank'] = 'Rank ' + customBreakup.start;
                } else {
                  customPrice[key]['rank'] = customBreakup.name;
                }
                customPrice[key]['price'] = customBreakup.price;
                if (first_prize == '') {
                  first_prize = customBreakup.price;
                }
              })
            }
            console.log("customPrice --------->", customPrice)

            let customPricemain = {};
            let winning_amount_maximum = 0;

            if (ele.contest && ele.contest.is_adjustable) {
              console.log("ele.contest -------->", ele.contest)
              let custom_breakupmain = await CustomBreakupmain.findAll({ raw: true, where: { contest_id: ele.contest.id, match_id: decoded.match_id } })
              custom_breakupmain = JSON.parse(JSON.stringify(custom_breakupmain))
              console.log("custom_breakupmain ------------->", custom_breakupmain)
              if (custom_breakupmain && custom_breakupmain.length > 0) {
                custom_breakupmain.map((customBreakup, key) => {

                  if (!customPricemain[key]) {
                    customPricemain[key] = {}
                  }

                  if (customBreakup.start == customBreakup.end) {
                    customPricemain[key]['rank'] = 'Rank ' + customBreakup.start;
                  } else {
                    customPricemain[key]['rank'] = customBreakup.name;
                  }
                  customPricemain[key]['price'] = customBreakup.price;

                  //Calculate Prize Pool
                  var levelWinner = (customBreakup.end - (customBreakup.start - 1));
                  var levelPrize = (levelWinner * customBreakup.price);
                  winning_amount_maximum = winning_amount_maximum + levelPrize;
                })
              }
            }


            let max_team_user = contestInfo.max_team_user;
            let winComfimed = 'no';
            if (contestInfo.confirmed_winning == '' || contestInfo.confirmed_winning == '0') {
              winComfimed = 'no';
            } else {
              winComfimed = contestInfo.confirmed_winning;
            }

            if (teamsJoined < contestInfo.contest_size) {

              let dynamic_contest_message = '';
              if (ele.contest && ele.contest.is_adjustable) {
                dynamic_contest_message = constants.dynamic_contest_message;
              }
              if (!contest[contestKey]) {
                contest[contestKey] = {}
              }
              contest[contestKey]['confirm_winning'] = winComfimed;
              contest[contestKey]['entry_fee'] = contestInfo.entry_fee;
              contest[contestKey]['prize_money'] = contestInfo.winning_amount;
              contest[contestKey]['total_teams'] = contestInfo.contest_size;
              contest[contestKey]['category_id'] = contestInfo.category_id ? contestInfo.category_id : '';
              contest[contestKey]['contest_id'] = contestInfo.id;
              contest[contestKey]['total_winners'] = +toalWinner;
              contest[contestKey]['teams_joined'] = teamsJoined;
              contest[contestKey]['is_joined'] = myTeamIds && myTeamIds.length > 0 ? true : false;
              contest[contestKey]['multiple_team'] = contestInfo.multiple_team == 'yes' ? true : false;
              contest[contestKey]['max_team_user'] = max_team_user;
              contest[contestKey]['usable_bonus_percentage'] = contestInfo.usable_bonus_percentage;
              contest[contestKey]['invite_code'] = ele.invite_code;
              contest[contestKey]['breakup_detail'] = Object.values(customPrice) || [];
              contest[contestKey]['my_team_ids'] = myTeamIds;
              contest[contestKey]['trump_mode'] = contestInfo.trump_mode;
              contest[contestKey]['winning_amount_maximum'] = winning_amount_maximum + "";
              contest[contestKey]['dynamic_contest_message'] = dynamic_contest_message;
              contest[contestKey]['is_adjustable'] = ele.contest.is_adjustable;
              contest[contestKey]['breakup_detail_maximum'] = Object.values(customPricemain) || [];
              contest[contestKey]['first_prize'] = first_prize + "";

            } else {
              await MatchContest.update(
                { is_full: 1 },
                { match: ele.match_id, contest_id: ele.contest_id }
              )
            }
            let contest1 = Object.values(contest);
            let newCounter = categoryArray[categoryId];
            if (!data[newCounter]) {
              data[newCounter] = {}
            }
            data[newCounter]['contests'] = contest1;
          }, Promise.resolve())
          // console.log("data ------------>", data)
          // console.log("contests ------------>", contest)
          if (Object.keys(data).length > 0) {
            for (var [catKey, category] in data) {
              if (data.hasOwnProperty(catKey) && !data[catKey]['contests']) {
                delete data[catKey];
              }
            }
          }
        }

        let myTeams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'], match_id: decoded['match_id']
          },
          raw: true
        })
        console.log("myTeams ---------->", myTeams)


        let my_nteams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
            trump_mode: 0
          },
          raw: true
        })

        console.log("my_nteams ---------->", my_nteams)


        let my_tteams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
            trump_mode: 1
          },
          raw: true
        })
        console.log("my_tteams ---------->", my_tteams)

        let myContest = await PlayerTeamContests.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
          },
          raw: true,
          group: ["contest_id"]
        })

        console.log("myContest ---------->", myContest)

        data1.match_contest = Object.values(data);
        data1.my_teams = myTeams;
        data1.my_nteams = my_nteams;
        data1.my_tteams = my_tteams;
        data1.my_contests = myContest.length || 0;
        status = true;
      }
      else {
        message = "Match id is empty."
      }
    } else {
      message = "You are not authenticated user."
    }
    let response_data = { 'status': status, 'tokenexpire': 0, 'message': message, 'data': data1 }
    res.json(response_data)
  } catch (err) {
    console.log(err)
    let response_data = { 'status': false, 'tokenexpire': 0, 'message': err.message || "Something went wrong", 'data': {} }
    res.json(response_data)
  }
};

const contestListAll = async function (req, res) {
  try {
    let status = false;
    let message = null;
    let data = {};
    let data1 = {};
    let data_row = [];
    let decoded = req.body

    if (decoded) {
      if (decoded['match_id']) {
        let trump_mode = decoded['trump_mode'] ? 1 : 0;
        let category_id = decoded['category_id'] ? 1 : 0;

        let condition = {}
        if (category_id) {
          condition = {
            status: 1,
            trump_mode: trump_mode,
            category_id: decoded.category_id
          }
        } else {
          condition = {
            status: 1,
            trump_mode: trump_mode,
          }
        }

        let result = await MatchContest.findAll({
          include: [
            {
              model: SeriesSquad,
              required: true,
              where: {
                match_id: decoded.match_id
              },
              attributes: ["match_id",]
            },
            {
              model: Contest,
              required: true,
              where: condition,
              include: [
                {
                  model: Category,
                  required: false,
                  attributes: ['id', 'image', 'category_name', 'description', 'category_color']
                },
                {
                  model: CustomBreakup,
                  required: false,
                  attributes: ['id', 'contest_id', 'name', 'start', 'end', 'percentage', 'price']
                }
              ],
              attributes: ['id', 'confirmed_winning', 'contest_size', 'entry_fee', 'winning_amount', 'category_id', 'multiple_team', 'max_team_user', 'trump_mode', 'usable_bonus_percentage', 'is_adjustable']
            },
          ],
          where: {
            is_full: 0,
          }
        })




        result = JSON.parse(JSON.stringify(result))

        let teamsJoinedContestWiseArray = await PlayerTeamContests.count({
          group: ["contest_id"],
          raw: true,
          where: {
            match_id: decoded.match_id
          }
        });

        let teamsJoinedContestWise = {};
        teamsJoinedContestWiseArray.map(ele => {
          teamsJoinedContestWise[ele.contest_id] = ele.count
        })

        let isJoined = await PlayerTeamContests.findAll({
          attributes: ["player_team_id", "contest_id"],
          where: {
            match_id: decoded.match_id,
            user_id: decoded.user_id
          },
          raw: true,
        })

        let myContestTeamIds = {}
        if (isJoined.length > 0) {
          isJoined.map(ele => {
            myContestTeamIds[ele.contest_id] = ele.player_team_id
          })
        }

        let counter = 0;
        let categoryArray = {}
        var contest = {};
        if (result && result.length > 0) {
          result.reduce(async (previousPromise, ele, contestKey) => {
            await previousPromise;
            let categoryInfo = ele.contest && ele.contest.category ? ele.contest.category : "";
            let categoryId = categoryInfo['id'];



            let contestInfo = ele.contest;

            let customBreakup = contestInfo.custom_breakup;
            if (customBreakup && customBreakup.end) {
              let toalWinner = customBreakup.end;
            } else {
              toalWinner = customBreakup ? customBreakup.start : 0;
            }

            // find team that other users joined
            let teamsJoined = teamsJoinedContestWise[contestInfo.id] ? teamsJoinedContestWise[contestInfo.id] : 0;


            let myTeamIds = [];
            if (myContestTeamIds) {
              myTeamIds = myContestTeamIds[contestInfo.id] ? myContestTeamIds[contestInfo.id] : [];
            }

            var customPrice = {};
            var first_prize = '';
            console.log("contestInfo.custom_breakup", contestInfo.custom_breakups)
            if (contestInfo.custom_breakups && contestInfo.custom_breakups.length > 0) {
              contestInfo.custom_breakups.map((customBreakup, key) => {
                if (!customPrice[key]) {
                  customPrice[key] = {}
                }
                if (customBreakup.start == customBreakup.end) {
                  customPrice[key]['rank'] = 'Rank ' + customBreakup.start;
                } else {
                  customPrice[key]['rank'] = customBreakup.name;
                }
                customPrice[key]['price'] = customBreakup.price;
                if (first_prize == '') {
                  first_prize = customBreakup.price;
                }
              })
            }
            console.log("customPrice --------->", customPrice)

            let customPricemain = {};
            let winning_amount_maximum = 0;

            if (ele.contest && ele.contest.is_adjustable) {
              console.log("ele.contest -------->", ele.contest)
              let custom_breakupmain = await CustomBreakupmain.findAll({ raw: true, where: { contest_id: ele.contest.id, match_id: decoded.match_id } })
              custom_breakupmain = JSON.parse(JSON.stringify(custom_breakupmain))
              console.log("custom_breakupmain ------------->", custom_breakupmain)
              if (custom_breakupmain && custom_breakupmain.length > 0) {
                custom_breakupmain.map((customBreakup, key) => {

                  if (!customPricemain[key]) {
                    customPricemain[key] = {}
                  }

                  if (customBreakup.start == customBreakup.end) {
                    customPricemain[key]['rank'] = 'Rank ' + customBreakup.start;
                  } else {
                    customPricemain[key]['rank'] = customBreakup.name;
                  }
                  customPricemain[key]['price'] = customBreakup.price;

                  //Calculate Prize Pool
                  var levelWinner = (customBreakup.end - (customBreakup.start - 1));
                  var levelPrize = (levelWinner * customBreakup.price);
                  winning_amount_maximum = winning_amount_maximum + levelPrize;
                })
              }
            }


            let max_team_user = contestInfo.max_team_user;
            let winComfimed = 'no';
            if (contestInfo.confirmed_winning == '' || contestInfo.confirmed_winning == '0') {
              winComfimed = 'no';
            } else {
              winComfimed = contestInfo.confirmed_winning;
            }

            if (teamsJoined < contestInfo.contest_size) {

              let dynamic_contest_message = '';
              if (ele.contest && ele.contest.is_adjustable) {
                dynamic_contest_message = constants.dynamic_contest_message;
              }
              if (!contest[contestKey]) {
                contest[contestKey] = {}
              }
              contest[contestKey]['confirm_winning'] = winComfimed;
              contest[contestKey]['entry_fee'] = contestInfo.entry_fee;
              contest[contestKey]['prize_money'] = contestInfo.winning_amount;
              contest[contestKey]['total_teams'] = contestInfo.contest_size;
              contest[contestKey]['category_id'] = contestInfo.category_id ? contestInfo.category_id : '';
              contest[contestKey]['contest_id'] = contestInfo.id;
              contest[contestKey]['total_winners'] = +toalWinner;
              contest[contestKey]['teams_joined'] = teamsJoined;
              contest[contestKey]['is_joined'] = myTeamIds && myTeamIds.length > 0 ? true : false;
              contest[contestKey]['multiple_team'] = contestInfo.multiple_team == 'yes' ? true : false;
              contest[contestKey]['max_team_user'] = max_team_user;
              contest[contestKey]['usable_bonus_percentage'] = contestInfo.usable_bonus_percentage;
              contest[contestKey]['invite_code'] = ele.invite_code;
              contest[contestKey]['breakup_detail'] = Object.values(customPrice) || [];
              contest[contestKey]['my_team_ids'] = myTeamIds;
              contest[contestKey]['trump_mode'] = contestInfo.trump_mode;
              contest[contestKey]['winning_amount_maximum'] = winning_amount_maximum + "";
              contest[contestKey]['dynamic_contest_message'] = dynamic_contest_message;
              contest[contestKey]['is_adjustable'] = ele.contest.is_adjustable;
              contest[contestKey]['breakup_detail_maximum'] = Object.values(customPricemain) || [];
              contest[contestKey]['first_prize'] = first_prize + "";

            } else {
              await MatchContest.update(
                { is_full: 1 },
                { match: ele.match_id, contest_id: ele.contest_id }
              )
            }
            let contest1 = Object.values(contest);
            let newCounter = categoryArray[categoryId];
            if (!data[newCounter]) {
              data[newCounter] = {}
            }
            data[newCounter]['contests'] = contest1;
          }, Promise.resolve())
          // console.log("data ------------>", data)
          // console.log("contests ------------>", contest)
          if (Object.keys(data).length > 0) {
            for (var [catKey, category] in data) {
              if (data.hasOwnProperty(catKey) && !data[catKey]['contests']) {
                delete data[catKey];
              }
            }
          }
        }

        let myTeams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'], match_id: decoded['match_id']
          },
          raw: true
        })
        console.log("myTeams ---------->", myTeams)


        let my_nteams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
            trump_mode: 0
          },
          raw: true
        })

        console.log("my_nteams ---------->", my_nteams)


        let my_tteams = await PlayerTeams.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
            trump_mode: 1
          },
          raw: true
        })
        console.log("my_tteams ---------->", my_tteams)

        let myContest = await PlayerTeamContests.count({
          where: {
            user_id: decoded['user_id'],
            match_id: decoded['match_id'],
          },
          raw: true,
          group: ["contest_id"]
        })

        console.log("myContest ---------->", myContest)

        data1.match_all_contest = Object.values(contest);
        data1.my_teams = myTeams;
        data1.my_nteams = my_nteams;
        data1.my_tteams = my_tteams;
        data1.my_contests = myContest.length || 0;
        status = true;
      }
      else {
        message = "Match id is empty."
      }
    } else {
      message = "You are not authenticated user."
    }
    let response_data = { 'status': status, 'tokenexpire': 0, 'message': message, 'data': data1 }
    res.json(response_data)
  } catch (err) {
    let response_data = { 'status': false, 'tokenexpire': 0, 'message': err.message || "Something went wrong", 'data': {} }
    res.json(response_data)
  }
};

module.exports = {
  getMatchList,
  contestpageapi,
  contestList,
  contestListAll
};
