//External Imports
let moment = require("moment");
let crypto = require("crypto");
let _ = require("lodash");
let util = require("util");

//Custom Imports
const constants = require("../constant/constants");

exports.last = (array) => {
    return array[array.length - 1];
}

/**
 * @description To generate random number
 * @param {*} length
 */
exports.generateRandomNumber = (length = 4) => {
    let text = "";
    let possible = "123456789";
    for (let i = 0; i < length; i++) {
        let sup = Math.floor(Math.random() * possible.length);
        text += i > 0 && sup == i ? "0" : possible.charAt(sup);
    }

    return text;
};

/**
 * @description To generate random Key
 * @param {*} length
 */
exports.generateRandomKey = (length = 8, walletKey = false) => {
    if (walletKey) {
        return (
            "TRNO" +
            exports.generateRandomNumber(2) +
            Math.round(new Date().getTime() / 1000)
        ).toUpperCase();
    }
    return crypto.randomBytes(length).toString("hex").toUpperCase();
};

/**
 * @description To generate random string
 * @param {*} length
 */
exports.generateRandomString = (length = 5) => {
    let text = "";
    let characters =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_0123456789";

    for (let i = 0; i < length; i++) {
        let sup = Math.floor(Math.random() * characters.length);
        text += characters.charAt(sup);
    }

    return text;
};

/**
 * @description To replace content place holder
 * @param {*} date
 */
exports.replaceContentPlaceHolder = (content, placeholderValue) => {
    return _.template(content)(placeholderValue);
};

/**
 * @description To check is empty
 */
exports.isEmpty = (str) => {
    if (!str) return true;

    str = str.replace(" ", "");
    return _.isEmpty(str);
};

/**
 * @description To get correct date
 * @param {*} date
 */
exports.formatedDate = (date, format) => {
    //"DD-MMM-YYYY, hh:mm:ss a"
    // return moment(date).utcOffset("+05:30").format(format);
    return moment(date).format(format);
};

/**
 * @description To get date difference
 * @param {*} date
 */
exports.getDateDiff = (
    startDate,
    endDate,
    format1 = "YYYY-MM-DD",
    format2 = "YYYY-MM-DD"
) => {
    const date1 = moment(startDate, format1);
    const date2 = moment(endDate, format2);

    return date2.diff(date1, "days");
};

/**
 * @description To subtract date
 */
exports.subtractDate = (date, subtractValue, subtractType, format) => {
    //
    return moment(date)
        .subtract(subtractValue, subtractType)
        .utcOffset("+05:30")
        .format(format);
};

/**
 * @description To add date
 */
exports.addDate = (date, addValue, addType, format) => {
    //
    return moment(date).add(addValue, addType).utcOffset("+05:30").format(format);
};

/**
 * @public
 * @description create response when error occurred
 */
exports.errorResponse = (res, code, message) => {
    //ERROR RESPONSE
    return res.status(code).json({
        error: true,
        code: code,
        message: message,
        data: {},
    });
};

/**
 * @public
 * @description create response when Internal server error occurred
 */
exports.serverErrorResponse = (res, msg, error) => {
    //
    console.error(util.format(`Error occure while ${msg} %O`, error));
    //ERROR RESPONSE
    return res.status(constants.OK.code).json({
        error: true,
        code: constants.OK.code,
        message: constants.INTERNAL_SERVER_ERROR.message,
        data: {},
    });
};

/**
 * @public
 * @description create response without error
 */
exports.correctResponse = (res, code, message, data) => {
    //RESPONSE
    return res.status(code).json({
        error: false,
        code: code,
        message: message,
        data: data,
    });
};

/**
 * @public
 * @description To capitalize all string
 */
exports.capitalizeAll = (s) => {
    if (typeof s !== "string") return "";

    let strings = s.split(" ");
    let str = "";

    //
    for (let string of strings) {
        str += string.toUpperCase();
        //
        if (string !== strings[strings.length - 1]) {
            str += " ";
        }
    }
    return str;
};

/**
 * @public
 * @description To capitalize first char of any string
 */
exports.capitalizeFirst = (s) => {
    if (typeof s !== "string") return "";

    let strings = s.split(" ");
    let str = "";
    //
    for (let string of strings) {
        str += string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
        //
        if (string !== strings[strings.length - 1]) {
            str += " ";
        }
    }
    return str;
};

/**
 * @public
 * @description To get percentage or two value
 */
exports.getPercentage = (num, total) => {
    let percentage = 0;
    //
    if (total > 0) {
        percentage = (num / total) * 100;
    }

    return parseFloat(percentage.toFixed(2));
};

/**
 * @public
 * @description To get percentage or two value
 */
exports.getPercentageAmount = (total, percentage) => {
    let amount = 0;
    //
    if (total > 0) {
        amount = total * (percentage / 100);
    }

    return parseFloat(amount.toFixed(2));
};

/**
 * @public
 * @description To mobile validation
 */
exports.mobileValidation = (mobile) => {
    let rgx = /[^0-9]/g;
    let num = mobile.replace(".", "");
    let isValid = false;

    if (!isNaN(num) && !rgx.test(num) && num !== "") {
        isValid = true;
    }

    return isValid;
};

/**
 * @public
 * @description To parse point for page
 */
global.pointParse = (num) => {
    //
    if (num > parseInt(num)) return parseInt(num) + 1;

    return num;
};

/**
 * @public
 * @description To console log
 */
global.consoleLog = (log, error) => {
    //
    console.error(util.format(`${log} ERROR: %O`, error));
    return;
};


/**
 * @public
 * @description To update file path
 */
exports.updatePath = (filename) => {
    //
    return `/uploads/${filename}`;
};

/**
 * @description To calculate distance between two locations
 * @param {*} lat1
 * @param {*} lon1
 * @param {*} lat2
 * @param {*} lon2
 * @param {*} unit
 */
exports.calculateDistanceBetweenTwoLocation = (
    lat1,
    lon1,
    lat2,
    lon2,
    unit
) => {
    lat2 = parseFloat(lat2);
    lon2 = parseFloat(lon2);

    if (lat1 === lat2 && lon1 === lon2) {
        return 0;
    } else {
        let radlat1 = (Math.PI * lat1) / 180;
        let radlat2 = (Math.PI * lat2) / 180;
        let theta = lon1 - lon2;
        let radtheta = (Math.PI * theta) / 180;

        let dist =
            Math.sin(radlat1) * Math.sin(radlat2) +
            Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);

        if (dist > 1) {
            dist = 1;
        }

        dist = Math.acos(dist);
        dist = (dist * 180) / Math.PI;
        dist = dist * 60 * 1.1515;

        if (unit == "K") {
            dist = dist * 1.609344;
        }

        if (unit == "N") {
            dist = dist * 0.8684;
        }

        return dist;
    }
};

exports.getDistanceBetweenLocation = (lat1, lon1, lat2, lon2, unit) => {
    lat2 = parseFloat(lat2);
    lon2 = parseFloat(lon2);

    if (lat1 === lat2 && lon1 === lon2) {
        return 0;
    } else {
        let radlat1 = (Math.PI * lat1) / 180;
        let radlat2 = (Math.PI * lat2) / 180;
        let theta = lon1 - lon2;
        let radtheta = (Math.PI * theta) / 180;

        let dist =
            Math.sin(radlat1) * Math.sin(radlat2) +
            Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);

        if (dist > 1) {
            dist = 1;
        }

        dist = Math.acos(dist);
        dist = (dist * 180) / Math.PI;
        dist = dist * 60 * 1.1515;

        if (unit == "K") {
            dist = dist * 1.609344;
        }

        if (unit == "N") {
            dist = dist * 0.8684;
        }

        return dist.toFixed(2);
    }
};
