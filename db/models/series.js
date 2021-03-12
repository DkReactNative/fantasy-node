const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('series', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    file_path: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    id_api: {
      type: DataTypes.INTEGER,
      allowNull: true,
      comment: "id recived from api"
    },
    name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    squads_file: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    short_name: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    status: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 1,
      comment: "0=not active, 1=active"
    },
    dateend: {
      type: DataTypes.DATEONLY,
      allowNull: false
    },
    from_es: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
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
    tableName: 'series',
    timestamps: false
  });
};
