//Internal Imports
const path = require("path");

//External Imports
const multer = require("multer");

//Custom Imports
const config = require("../config/config");

let upload = multer({
  storage: multer.diskStorage({
    destination: function (req, file, cb) {
      cb(null, `${path.resolve()}${config.get("multer.uploadDirectoryPath")}`);
    },
    filename: function (req, file, cb) {
      let imgExtension = file.originalname.split(".");
      cb(null, `${Date.now()}.png`);
    },
  }),
});
module.exports = {
  upload: upload,
};
