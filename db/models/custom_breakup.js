const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('custom_breakup', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    contest_id: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    name: {
      type: DataTypes.STRING(25),
      allowNull: true
    },
    start: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    end: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    percentage: {
      type: DataTypes.STRING(25),
      allowNull: true
    },
    dpercentage: {
      type: DataTypes.STRING(25),
      allowNull: false
    },
    price: {
      type: DataTypes.STRING(25),
      allowNull: true
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'custom_breakup',
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
        name: "contest_id",
        using: "BTREE",
        fields: [
          { name: "contest_id" },
        ]
      },
    ]
  });
};
