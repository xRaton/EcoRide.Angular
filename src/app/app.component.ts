import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

interface Trip {
id: number;
pseudo: string;
photo: string;
note: number;
departure: string;
destination: string;
date: string;
heureDépart: string;
heureArrivée: string;
seatsAvailable: number;
price: number;
eco: boolean;
}
@Component({
  selector: 'app-root',
  imports: [
    FormsModule,
    CommonModule,
    RouterOutlet,
    RouterLink,
    RouterLinkActive
  ],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent {
  title = 'EcoRide-app';
  trips: Trip[] = [
      { id: 1, pseudo: 'marc', photo :'' , note : 4, departure: 'Paris', destination: 'Lyon', date: '01-06-2025', heureDépart:"14h35", heureArrivée:"15h", seatsAvailable: 2, price: 15, eco: true },
      { id: 2, pseudo: 'justin', photo :'', note : 4, departure: 'Marseille', destination: 'Nice', date: '05-06-2025', heureDépart:"14h35", heureArrivée:"15h", seatsAvailable: 3, price: 20, eco: true  },
      { id: 3, pseudo: 'camille', photo :'', note : 4, departure: 'Lille', destination: 'Paris', date: '10-06-2025', heureDépart:"14h35", heureArrivée:"15h", seatsAvailable: 1, price: 15.5, eco: false  }
    ];
  filteredTrips : Trip[]= [];
  searchDeparture: string= '';
  searchDestination: string= '';
  searchDate: string= '';
  searchPrice: number= 0;
  searchEco: boolean= false;
  searchHeureDépart: string='';
  searchHeureArrivée: string='';
  
  newTrip: Trip = {
    id: 0,
    pseudo:'',
    photo:'',
    note:0,
    departure: '',
    destination: '',
    date: '',
    heureDépart: '',
    heureArrivée: '',
    seatsAvailable: 0,
    price: 0,
    eco: false,
  };
  
  addTrip() {
    this.newTrip.id = this.trips.length + 1;
    this.trips.push({ ...this.newTrip });
    this.newTrip = { id: 0, pseudo: '', photo: '', note: 0, departure: '', destination: '', date: '',heureDépart: '', heureArrivée: '', seatsAvailable: 0, price: 0, eco: false, };
  }
}

