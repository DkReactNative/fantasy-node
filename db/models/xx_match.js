const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('xx_match', {
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    match_title: {
      type: DataTypes.STRING(100),
      allowNull: false
    },
    date: {
      type: DataTypes.DATEONLY,
      allowNull: false
    },
    series_name: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    total_game_play: {
      type: DataTypes.DECIMAL(34,2),
      allowNull: true
    },
    total_winning_distributed: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    profit: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    }
  }, {
    sequelize,
    tableName: 'xx_match',
    timestamps: false
  });
};
