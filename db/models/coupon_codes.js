const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('coupon_codes', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    coupon_code: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    min_amount: {
      type: DataTypes.STRING(150),
      allowNull: true,
      defaultValue: "0"
    },
    max_cashback_amount: {
      type: DataTypes.STRING(150),
      allowNull: true,
      defaultValue: "0"
    },
    max_cashback_percent: {
      type: DataTypes.STRING(50),
      allowNull: true,
      defaultValue: "0"
    },
    usage_limit: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    per_user_limit: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    expiry_date: {
      type: DataTypes.DATE,
      allowNull: true
    },
    status: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 1
    }
  }, {
    sequelize,
    tableName: 'coupon_codes',
    timestamps: false,
    indexes: [
      {
        name: "PRIMARY",
        unique: true,
        using: "BTREE",
        fields: [
          { name: "id" },
        ]
      },
      {
        name: "coupon_code",
        using: "BTREE",
        fields: [
          { name: "coupon_code" },
        ]
      },
      {
        name: "min_amount",
        using: "BTREE",
        fields: [
          { name: "min_amount" },
        ]
      },
      {
        name: "per_user_limit",
        using: "BTREE",
        fields: [
          { name: "per_user_limit" },
        ]
      },
    ]
  });
};
