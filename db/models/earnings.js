const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('earnings', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    series_squad_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      unique: "match_id"
    },
    match_title: {
      type: DataTypes.STRING(100),
      allowNull: false
    },
    date: {
      type: DataTypes.DATEONLY,
      allowNull: false
    },
    time: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    series_name: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    total_bonus_amount: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    total_winning_amount: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    total_deposit_cash: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    total_retainer_wallet: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    total_active_wallet: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    total_amount: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    total_winning_distributed: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    created: {
      type: DataTypes.DATE,
      allowNull: false
    },
    modifed: {
      type: DataTypes.DATE,
      allowNull: false
    }
  }, {
    sequelize,
    tableName: 'earnings',
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
        name: "match_id",
        unique: true,
        using: "BTREE",
        fields: [
          { name: "match_id" },
        ]
      },
      {
        name: "series_squad_id",
        using: "BTREE",
        fields: [
          { name: "series_squad_id" },
        ]
      },
    ]
  });
};
