var jwt = require('jsonwebtoken');
var fs = require('fs');
const PUBLIC_KEY = fs.readFileSync('config/public.key');
function verifyToken(req, res, next) {
  var token = req.headers['x-access-token'];
  if (!token)
    return res.status(403).send({ success: false, auth: false, msg: 'No token provided' });

  jwt.verify(token, PUBLIC_KEY, function (err, decoded) {
    if (err)
      return res.status(403).send({ success: false, auth: false, msg: 'Failed to authenticate token.  or token expired' });
    // if everything good, save to request for use in other routes
    //console.log(decoded);
    req.user_id = decoded.id;
    next();
  });
}

module.exports = verifyToken;