function applyExtraSetup(sequelize) {
  const { SeriesSquad, MatchContest, Series, MstTeams, Contest, Category, CustomBreakup } = sequelize.models;

  // console.log(sequelize.models)

  // SeriesSquad 
  SeriesSquad.belongsTo(Series, {
    foreignKey: "series_id",
    targetKey: "id_api",
  });


  SeriesSquad.belongsTo(MstTeams, {
    foreignKey: "visitorteam_id",
    targetKey: "team_id",
    as: "VisitorMstTeams",
  });


  SeriesSquad.belongsTo(MstTeams, {
    foreignKey: "localteam_id",
    targetKey: "team_id",
    as: "LocalMstTeams",
  });


  // MatchContest
  MatchContest.belongsTo(SeriesSquad, {
    foreignKey: "match_id",
    targetKey: "id",
  });

  MatchContest.belongsTo(Contest, {
    foreignKey: "contest_id",
    targetKey: "id",
  });


  //Contest
  Contest.belongsTo(Category, {
    foreignKey: "category_id",
    targetKey: "id",
  });

  Contest.hasMany(CustomBreakup, {
    foreignKey: "contest_id",
    targetKey: "id",
  });

}
module.exports = { applyExtraSetup };
