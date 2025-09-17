import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';


@Component({
  selector: 'app-vehicle',
  standalone: true,
  imports: [CommonModule,FormsModule],
  templateUrl: './vehicle.component.html'
})
export class VehiclesComponent implements OnInit {
  vehicles: any[] = [];
  newVehicle = {
    immatriculation: '',
    marque: '',
    modele: '',
    couleur: '',
    energie: '',
    seats: 4,
    date_premiere_immat: ''
  };

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    this.loadVehicles();
  }

  loadVehicles() {
    this.api.getMyVehicles().subscribe(res => this.vehicles = res);
  }

  addVehicle() {
    this.api.addVehicle(this.newVehicle).subscribe(() => {
      alert('Véhicule ajouté');
      this.loadVehicles();
    });
  }

  deleteVehicle(id: number) {
    this.api.deleteVehicle(id).subscribe(() => this.loadVehicles());
  }
}
