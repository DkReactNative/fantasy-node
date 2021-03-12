//Internal Imports
const path = require("path");

//External Imports
const bodyParser = require("body-parser");
const compression = require("compression");
const cookieParser = require("cookie-parser");
const cors = require("cors");

// Custom Imports
const config = require("../config/config");

module.exports = (app, express, root) => {
  // Enable compression
  if (config.get("server.enableCompression")) app.use(compression());

  // Enable Static Directory Path
  if (config.get("server.enableStatic")) {
    app.use(
      express.static(path.join(root, config.get("server.static.directory")))
    );
  }

  //Enable Cors Support
  if (config.get("server.security.enableCORS")) app.use(cors());//require("./cors")(app);

  //Enable request body parsing
  app.use(
    bodyParser.urlencoded({
      extended: false,
      limit: config.get("server.bodyParser.limit"),
    })
  );

  //Enable request body parsing in JSON format
  app.use(
    bodyParser.json({
      limit: config.get("server.bodyParser.limit"),
    })
  );

  // Enable cookie parsing
  app.use(cookieParser(config.get("server.session.cookieSecret")));
};
