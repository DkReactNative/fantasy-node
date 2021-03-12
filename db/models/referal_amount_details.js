const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('referal_amount_details', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    earn_by: {
      type: DataTypes.INTEGER,
      allowNull: false,
      comment: "That user Id who play game"
    },
    game_id: {
      type: DataTypes.ENUM('1','2','3','4','5','6','7','8','9','10','998','999'),
      allowNull: false,
      comment: "1-Cricket,2-Basketball,3-Rummy,4-Ludo,999-Convert Mega User Bonus,998-T20 Offer"
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: 0
    },
    contest_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    referal_level: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    referal_percentage: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    amount: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    is_deposieted: {
      type: DataTypes.TINYINT,
      allowNull: false,
      comment: "0-Not,1-Yes"
    },
    date: {
      type: DataTypes.DATEONLY,
      allowNull: false
    },
    flag: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    created: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: Sequelize.fn('current_timestamp')
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'referal_amount_details',
    timestamps: false
  });
};
