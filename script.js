
document.addEventListener('DOMContentLoaded',()=>{
 const q=s=>document.querySelector(s), qa=s=>document.querySelectorAll(s);
 const date=q('input[type="date"]'); if(date){let d=new Date();d.setMinutes(d.getMinutes()-d.getTimezoneOffset());date.min=d.toISOString().slice(0,10)}
 qa('[data-route]').forEach(b=>b.addEventListener('click',()=>location.href='search-results.html'));
 const sf=q('#searchForm'); if(sf) sf.addEventListener('submit',e=>{e.preventDefault();location.href='search-results.html'});
 qa('.view-seats').forEach(b=>b.addEventListener('click',()=>location.href='seat-selection.html'));
 const seats=q('#seats');
 if(seats){let taken=[4,7,15,19];for(let i=1;i<=24;i++){let b=document.createElement('button');b.className='seat'+(taken.includes(i)?' taken':'');b.textContent=i;b.disabled=taken.includes(i);b.onclick=()=>{qa('.seat').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');q('#seatNo').textContent=i;q('#continueSeat').disabled=false};seats.appendChild(b)}}
 const cs=q('#continueSeat');if(cs)cs.onclick=()=>location.href='payment.html';
 qa('form[data-next]').forEach(f=>f.addEventListener('submit',e=>{e.preventDefault();location.href=f.dataset.next}));
});
