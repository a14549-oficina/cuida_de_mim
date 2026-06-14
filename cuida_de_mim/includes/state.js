// HELPERS 
function hoje(offset=0){const d=new Date();d.setDate(d.getDate()+offset);return d.toISOString().split('T')[0]}
function proximaHora(h){const d=new Date();d.setHours(d.getHours()+h);return d.toISOString().slice(0,16)}

//  STATE (calendário) 
// Apenas para o calendário client-side (dias/meses navegação)
const STATE = {
  calMes: new Date().getMonth(),
  calAno: new Date().getFullYear(),
};
