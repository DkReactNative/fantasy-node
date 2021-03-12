const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('banners', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    banner_type: {
      type: DataTypes.TINYINT,
      allowNull: true
    },
    image: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    link: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    page_title: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    offer_id: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    series_id: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    match_id: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    status: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 1,
      comment: "0=not active, 1=active"
    },
    sequence: {
      type: DataTypes.INTEGER,
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
    tableName: 'banners',
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
        name: "banner_type",
        using: "BTREE",
        fields: [
          { name: "banner_type" },
        ]
      },
      {
        name: "offer_id",
        using: "BTREE",
        fields: [
          { name: "offer_id" },
        ]
      },
      {
        name: "series_id",
        using: "BTREE",
        fields: [
          { name: "series_id" },
        ]
      },
      {
        name: "match_id",
        using: "BTREE",
        fields: [
          { name: "match_id" },
        ]
      },
    ]
  });
};
