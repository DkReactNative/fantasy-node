const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('users', {
    id: {
      type: DataTypes.INTEGER.UNSIGNED,
      allowNull: false,
      primaryKey: true
    },
    first_name: {
      type: DataTypes.STRING(250),
      allowNull: true
    },
    last_name: {
      type: DataTypes.STRING(250),
      allowNull: true
    },
    role_id: {
      type: DataTypes.INTEGER,
      allowNull: true,
      comment: "1 = admin, 2 = user, 3 = subadmin"
    },
    email: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    phone: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    password: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    team_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    date_of_bith: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    gender: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 1,
      comment: "0=Female, 1=male"
    },
    country: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    state: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    city: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    postal_code: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    address: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    image: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    image_updated: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    fb_id: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    google_id: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    refer_id: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    refer_percentage: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    otp: {
      type: DataTypes.STRING(10),
      allowNull: true
    },
    otp_time: {
      type: DataTypes.DATE,
      allowNull: true
    },
    is_login: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 0
    },
    last_login: {
      type: DataTypes.DATE,
      allowNull: true
    },
    device_id: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    device_type: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    module_access: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    current_password: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    language: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    app_version: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    cash_balance: {
      type: DataTypes.STRING(50),
      allowNull: true,
      defaultValue: "0"
    },
    winning_balance: {
      type: DataTypes.STRING(50),
      allowNull: true,
      defaultValue: "0"
    },
    bonus_amount: {
      type: DataTypes.STRING(50),
      allowNull: true,
      defaultValue: "0"
    },
    status: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    is_updated: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 0,
      comment: "0=>Not Updated,1=>Updated"
    },
    email_verified: {
      type: DataTypes.TINYINT,
      allowNull: true,
      comment: "0=>Not Verified;1=>.verified"
    },
    verify_string: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    sms_notify: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 1,
      comment: "1=>true,0=>false"
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'users',
    timestamps: false
  });
};
