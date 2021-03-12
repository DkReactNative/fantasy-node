const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('series_players', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    series_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    team_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    team_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    player_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    player_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    player_role: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    odi: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    t20: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    t10: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    test: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    is_record_fatch: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 0
    },
    fatch_date: {
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
    tableName: 'series_players',
    timestamps: false
  });
};
