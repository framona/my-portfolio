import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import VetDashboard from './vetDashboard';
import EditProfile from '../common/editProfile';
import ChangePassword from '../common/changePassword';

const Stack = createNativeStackNavigator();

export default function VetStack({ onLogout }) {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="VetDashboard">
        {(props) => <VetDashboard {...props} onLogout={onLogout} />}
      </Stack.Screen>
      <Stack.Screen name="EditProfile" component={EditProfile} />
      <Stack.Screen name="ChangePassword" component={ChangePassword} />
    </Stack.Navigator>
  );
}