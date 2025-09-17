import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BookingsListComponent } from './bookings.component';

describe('BookingsListComponent', () => {
  let component: BookingsListComponent;
  let fixture: ComponentFixture<BookingsListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BookingsListComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BookingsListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
