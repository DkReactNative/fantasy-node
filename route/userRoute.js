var express = require("express");
var router = express.Router();
const indexRoute = require("./userRoute/indexRoute");
router.use("/", indexRoute);
module.exports = router;
