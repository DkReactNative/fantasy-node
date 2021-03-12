module.exports = {
  OK: {
    code: 200,
    message: "OK",
  },
  CREATED: {
    code: 201,
    message: "CREATED",
  },
  ACCEPTED: {
    code: 202,
    message: "ACCEPTED",
  },
  NON_AUTHORITATIVE_INFORMATION: {
    code: 203,
    message: "NON_AUTHORITATIVE_INFORMATION ",
  },
  NO_CONTENT: {
    code: 204,
    message: "NO_CONTENT",
  },
  RESET_CONTENT: {
    code: 205,
    message: "RESET_CONTENT",
  },
  PARTIAL_CONTENT: {
    code: 206,
    message: "PARTIAL_CONTENT",
  },
  MULTI_STATUS: {
    code: 207,
    message: "MULTI_STATUS",
  },
  ALREADY_REPORTED: {
    code: 208,
    message: "ALREADY_REPORTED ",
  },
  IM_USED: {
    code: 226,
    message: "IM_USED",
  },
  BAD_REQUEST: {
    code: 400,
    message: "BAD_REQUEST",
  },
  UNAUTHORIZED: {
    code: 401,
    message: "UNAUTHORIZED",
  },
  PAYMENT_REQUIRED: {
    code: 402,
    message: "PAYMENT_REQUIRED",
  },
  FORBIDDEN: {
    code: 403,
    message: "ACCESS FORBIDDEN",
  },
  NOT_FOUND: {
    code: 404,
    message: "NOT_FOUND",
  },
  METHOD_NOT_ALLOWED: {
    code: 405,
    message: "METHOD_NOT_ALLOWED",
  },

  INTERNAL_SERVER_ERROR: {
    code: 500,
    message: "INTERNAL_SERVER_ERROR",
  },

  messageKeys: {
    code_2000: "success",
    code_2001: "User created successfully.",
    code_2002: "Session created.",
    code_2003: "Successful login.",
    code_2004: "Mail sent successfully.",
    code_4000: "Bad Request",
    code_4001: "Invalid credentials.",
    code_4002: "Incomplete data.",
    code_4003: "User already logged in somewhere.",
    code_4004: "Unauthorized access.",
    code_4005: "Invalid CSRF token.",
    code_4006: "Token missing.",
    code_4007: "Invalid Link.",
    code_5000: "An error occured while authentiation.",
    code_5001: "An error occured while creating user.",
    code_5002: "Internal server error.",
    code_5003: "File not uploaded. Error on creating file.",
    code_5004: "Error in reading file.",
  },
  TABLES: {
    USER: "user",
    USER_ROLES: "role",
    WALLET: "wallet",
    REFERRAL_CODE: "referral_code",
  },
  NOTIFICATION_CONTENT: {
    MOBILE_VERIFICATION: `<%= otp %> is the OTP to verify your mobile no.`,
  },
  REFFER_CODE: {
    PREFIX: "TR",
  },
  PREFIXS: {
    BOOKING_ID: "TRB-",
  },
  ROLES: {
    Rider: 1,
    1: "Rider",
    Driver: 2,
    2: "Driver",
    Admin: 3,
    3: "Admin",
  },
  USER_STATUS: {
    Not_Verified: 0,
    Verified: 1,
  },
  DOCUMENT_STATUS: {
    Verified: 1,
    Not_Verified: 0,
    In_Progress: 2,
    Decline: 3,
    Delete: 4,
  },
  VEHICLE_STATUS: {
    Deactive: 0,
    Active: 1,
    Delete: 2,
  },
  BOOKING_TYPE: {
    RIDE: "Ride",
    DELIVERY: "Delivery",
    PICKUP: "Pickup",
  },
  DISCOUNT_TYPE: { PERCENT: "Percent", AMOUNT: "Amount" },
  BOOKING_TYPE_ENUM: ["Ride", "Delivery", "Pickup"],
  PAYMENT_MODE: ["Online", "Offline"],
  OTP_FOR: { VERIFY: "Verify", OTHER: "other" },
  WALLET: {
    WALLET_CODE_PREFIX: "TRWALLET00",
    TYPE: {
      CREDIT: "CR",
      DEBIT: "DR",
    },
  },
  EMAIL: {
    FROM: "ramanmathur14@gmail.com",
    SEND_OTP_SUBJECT: "Time Rider",
    CONNECTION_HOST: "smtp.gmail.com",
    CONNECTION_USERNAME: "ramanmathur14@gmail.com",
    CONNECTION_PASSWORD: "raman###mathur",
  },
  TWILIO: {
    ACCOUNT_SID: "",
    AUTH_TOKEN: "",
    FORM: "+16503811807",
  },
  DOCUMENT_TYPES: [
    "driving_licence",
    "credit_card",
    "safety_check",
    "bank_detail",
  ],
  dynamic_contest_message: 'Dynamic: Contest will get Confirmed if more than 6 teams joined and prizes are calculated as per slots filled otherwise it get cancelled.'
};
