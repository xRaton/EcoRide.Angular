import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-ride-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './ride-list.component.html'
})
export class RideListComponent implements OnInit {
  rides: any[] = [];

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.api.getAllRides().subscribe(res => this.rides = res);
  }
}
