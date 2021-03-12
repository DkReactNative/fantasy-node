const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('settings', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    admin_email: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    admin_address: {
      type: DataTypes.TEXT,
      allowNull: false
    },
    admin_percentage: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    vendor_percentage: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    referral_bouns_amount: {
      type: DataTypes.STRING(100),
      allowNull: false
    },
    referral_bouns_amount_referral: {
      type: DataTypes.STRING(100),
      allowNull: false
    },
    referral_code: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    tds: {
      type: DataTypes.STRING(10),
      allowNull: false,
      defaultValue: "31.2"
    },
    game_join_time: {
      type: DataTypes.STRING(100),
      allowNull: false
    },
    salary_cap: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    site_name: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    site_logo: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    admin_background: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    date_format: {
      type: DataTypes.STRING(100),
      allowNull: false
    },
    meta_title: {
      type: DataTypes.STRING(150),
      allowNull: false
    },
    currency: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    site_meta_description: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    paytm_environment: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    paytm_merchant_key: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    paytm_merchant_mid: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    paytm_merchant_website: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    facebook_app_id: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    facebook_app_secret: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    google_app_id: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    google_app_secret: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    coutn_down_date: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    contest_commission: {
      type: DataTypes.DOUBLE,
      allowNull: false
    },
    min_withdraw_amount: {
      type: DataTypes.DOUBLE,
      allowNull: false
    },
    min_deposit_for_referral: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    created: {
      type: DataTypes.DATE,
      allowNull: false
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: false
    }
  }, {
    sequelize,
    tableName: 'settings',
    timestamps: false
  });
};
