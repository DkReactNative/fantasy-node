const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('contest', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    category_id: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    contest_name: {
      type: DataTypes.STRING(250),
      allowNull: true
    },
    admin_comission: {
      type: DataTypes.STRING(10),
      allowNull: true
    },
    winning_amount: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    contest_size: {
      type: DataTypes.STRING(15),
      allowNull: true,
      comment: "Team Size"
    },
    min_contest_size: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    contest_type: {
      type: DataTypes.STRING(15),
      allowNull: true,
      comment: "Paid or Free"
    },
    entry_fee: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    confirmed_winning: {
      type: DataTypes.STRING(5),
      allowNull: true,
      comment: "Confirmed winning even if the contest remains unfilled"
    },
    multiple_team: {
      type: DataTypes.STRING(5),
      allowNull: true,
      comment: "Join with multiple teams"
    },
    max_team_user: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: 1,
      comment: "1=single, any number = multiple"
    },
    auto_create: {
      type: DataTypes.STRING(5),
      allowNull: true,
      comment: "Auto create"
    },
    status: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 1,
      comment: "0= not active, 1= active"
    },
    price_breakup: {
      type: DataTypes.INTEGER,
      allowNull: true,
      comment: "0 = price breakup not created, 1 = price breakup created"
    },
    invite_code: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    is_auto_create: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 1,
      comment: "1=>false,2=>true"
    },
    parent_id: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    trump_mode: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    copied: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    usable_bonus_percentage: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    winning_amount_percentage: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    winner_percentage: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    is_adjustable: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'contest',
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
        name: "category_id",
        using: "BTREE",
        fields: [
          { name: "category_id" },
        ]
      },
      {
        name: "contest_size",
        using: "BTREE",
        fields: [
          { name: "contest_size" },
        ]
      },
      {
        name: "contest_type",
        using: "BTREE",
        fields: [
          { name: "contest_type" },
        ]
      },
      {
        name: "entry_fee",
        using: "BTREE",
        fields: [
          { name: "entry_fee" },
        ]
      },
      {
        name: "confirmed_winning",
        using: "BTREE",
        fields: [
          { name: "confirmed_winning" },
        ]
      },
      {
        name: "auto_create",
        using: "BTREE",
        fields: [
          { name: "auto_create" },
        ]
      },
      {
        name: "is_auto_create",
        using: "BTREE",
        fields: [
          { name: "is_auto_create" },
        ]
      },
    ]
  });
};
