const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('player_record', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    player_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    player_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    image: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    age: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    born: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    playing_role: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    batting_style: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    bowling_style: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    country: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    batting_odiStrikeRate: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    batting_odiAverage: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    bowling_odiAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_odiStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_firstClassStrikeRate: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    batting_firstClassAverage: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    bowling_firstClassStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_firstClassAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_t20iStrikeRate: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    batting_t20iAverage: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    bowling_t20iStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_t20iAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_testStrikeRate: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    batting_testAverage: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    bowling_testStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_testAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_listAStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_listAAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_listAStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_listAAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_t20sStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    batting_t20sAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_t20sStrikeRate: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    bowling_t20sAverage: {
      type: DataTypes.STRING(15),
      allowNull: true
    },
    teams: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    player_credit: {
      type: DataTypes.STRING(50),
      allowNull: true
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
    tableName: 'player_record',
    timestamps: false
  });
};
