var express = require("express");
let router = express.Router();
var indexController = require("../../controller/user/indexController");
router.route("/getMatchList").post(indexController.getMatchList);
router.route("/contestpageapi").post(indexController.contestpageapi);
router.route("/contestList").post(indexController.contestList);
router.route("/contestListAll").post(indexController.contestListAll);
module.exports = router;
