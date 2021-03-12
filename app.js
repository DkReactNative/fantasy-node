require("dotenv").config();
var createError = require("http-errors");
var express = require("express");
var path = require("path");
var bodyParser = require("body-parser");
var logger = require("morgan");
var cors = require("cors");

var app = express();

var corsOption = {
  methods: "GET,HEAD,PUT,PATCH,POST,DELETE",
  credentials: true,
  exposedHeaders: ["x-access-token"],
};

app.use(cors(corsOption));
// view engine setup
app.set("views", path.join(__dirname, "views"));
app.set("view engine", "ejs");
app.use(logger("dev"));

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

app.use(express.static("public"));
app.use(express.static(path.join(__dirname, "public")));

const userRoute = require("./route/userRoute");
app.get("/", (req, res) => {
  res.send("welcome")
})
app.use("/WebServices/", userRoute);

app.use(function (req, res, next) {
  next(createError(404));
});

String.prototype.toPascalCase = function () {
  return this.replace(new RegExp(/[-_]+/, "g"), " ")
    .replace(new RegExp(/[^\w\s]/, "g"), "")
    .replace(
      new RegExp(/\s+(.)(\w+)/, "g"),
      ($1, $2, $3) => `${$2.toUpperCase() + $3.toLowerCase()}`
    )
    .replace(new RegExp(/\s/, "g"), "")
    .replace(new RegExp(/\w/), (s) => s.toUpperCase());
};

app.use(function (err, req, res, next) {
  res.locals.message = err.message;
  res.locals.error = req.app.get("env") === "development" ? err : {};

  res.status(err.status || 500);
  res.render("error");
});

module.exports = app;
